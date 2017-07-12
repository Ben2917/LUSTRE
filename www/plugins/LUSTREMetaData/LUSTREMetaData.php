<?php

/**
 * LUSTRE Meta Data Plugin
 */

class LUSTREMetaData extends Omeka_Plugin_AbstractPlugin {

    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('install', 'uninstall', 'after_save_item');
    
    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_items_form_tabs');
    
    /**
     * Install the plugin.
     */
    public function hookInstall() {
        $elementSetMetadata = array(
            'name'        => 'LUSTRE', 
            'description' => 'Adds LUSTRE specific project information'
        );

        $elements = array(
        array(
            'name'  =>  'Supervisor',
            'description' => 'Name of the project supervisor'
        ),
        array( // Do we need a post grad level?
            'name' => 'Project Level',
            'description' => 'Project levels should be entered as UG or MSC'
        ),
        array(
            'name' => 'Topic',
            'description' => 'Should contain the sub-category of Psychology the project falls under'
        ),
        array(
            'name' => 'Statistical Anlaysis Type',
            'description' => 'Type of statistical analysis used'
        ),
        array(
            'name' => 'Sample Size'
        ));
            
        insert_element_set($elementSetMetadata, $elements);
    }  

    /**
    * Uninstall the plugin
    */
    public function hookUninstall()
    {
        $db = get_db();
        $elementSet = get_db()->getTable('ElementSet')->findByName('Book');
        $elementSet->delete();
    }


    /** 
    * After saving an item with data in the Book Element Set values are automatically
    * mapped on the Dublin Core Element Set. Preexisting DC values are deleted
    */
    
    public function hookAfterSaveItem($args)
    {   
        $item = $args['record'];
        $id = $item['id'];
        $db = get_db();
        $DCElementSetIDSelect = $db -> select() -> from (array('omeka_element_sets'), array('id')) -> where ('name = ?', 'Dublin Core');
        $DCElementSetID =  $db -> fetchOne($DCElementSetIDSelect);
        $DCElementIDsSelect = $db -> select() -> from (array('omeka_elements'), array('id')) -> where ('element_set_id = ?', $DCElementSetID);
        $DCElementIDs= $db -> fetchCol($DCElementIDsSelect);

        /**
        * Removes the content of DC fields if Book Element Set has values
        */

        $hasBookElements = $this->has_book_element_texts($item);
            
        if ($hasBookElements == true)
            {  
                foreach ($DCElementIDs as $DCElementID) {
                    $db->delete('omeka_element_texts', array('record_id =' . $id, 'element_id =' . $DCElementID));
                }
            }
        else {}

        /**
        * Mapping rules for fields from the Book Element Set to the Dublin Core Fields
        */

        $mapping = array('Title' => 'Title', 'Author/Editor' => 'Creator', 'Publisher' => 'Publisher',
            'Year Published' => 'Date', 'Blurb' => 'Description', 'Keywords' => 'Subject', 'Series' => 'Description', 
            'ISBN Print' => 'Identifier', 'ISBN PDF' => 'Identifier', 'ISBN EPUB' => 'Identifier',
            'DOI' => 'Identifier', 'Rights' => 'Rights', 'Language' => 'Language', 'Type' => 'Type',
            'Format' => 'Format');
        
        $bookElementSetIDSelect = $db -> select() -> from (array('omeka_element_sets'), array('id')) -> where ('name = ?', 'Book');
        $bookElementSetID =  $db -> fetchOne($bookElementSetIDSelect);

        $bookElementSubtitleIDSelect = $db -> select() -> from (array('omeka_elements'), array('id')) 
                                       -> where ('element_set_id = ?', $bookElementSetID)
                                       -> where ('name = ?', 'Subtitle');
        $bookElementSubtitleID =  $db -> fetchOne($bookElementSubtitleIDSelect);


        foreach ($mapping as $bookField => $DCField) {
            $bookElementTexts = $item->getElementTexts('Book', $bookField);
            foreach ($bookElementTexts as $bookElementText){
                
                $dcElementIDSelect = $db -> select() -> from (array('omeka_elements'), array('id')) 
                                       -> where ('element_set_id = ?', $DCElementSetID)
                                       -> where ('name = ?', $DCField);
                $dcID =  $db -> fetchOne($dcElementIDSelect);
                $bookElementIDSelect = $db -> select() -> from (array('omeka_elements'), array('id')) 
                                       -> where ('element_set_id = ?', $bookElementSetID)
                                       -> where ('name = ?', $bookField);
                $bookID =  $db -> fetchOne($bookElementIDSelect);

                $htmlSelect = $db -> select() -> from (array('omeka_element_texts'), array('html')) 
                                       -> where ('element_id = ?', $bookID)
                                       -> where ('record_id = ?', $id)
                                       -> where ('text = ?', $bookElementText);
                $html =  $db -> fetchOne($htmlSelect);

                /**
                * Combines Title and Subtitle Fields to DC:title = Title : Subtitle 1 : Subtitle 2
                * and checks if either Title or Subtitle has been marked up as HTML
                */

                if ($bookField == 'Title') {                                           
                    $bookSubtitles = $item->getElementTexts('Book', 'Subtitle');
                    foreach ($bookSubtitles as $bookSubtitleElement) {
                        $bookSubtitle = $bookSubtitle . ' : ' . $bookSubtitleElement;
                        
                      $htmlSubtitleSelect = $db -> select() -> from (array('omeka_element_texts'), array('html')) 
                                       -> where ('element_id = ?', $bookElementSubtitleID)
                                       -> where ('record_id = ?', $id)
                                       -> where ('text = ?', $bookSubtitleElement);
                        $htmlTemp =  $db -> fetchOne($htmlSubtitleSelect);

                        if ($htmlTemp == '1'){
                            $html= '1';
                        }
                    }

                    $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => $bookElementText . $bookSubtitle);
                    $db->insert('element texts', $dcElementValues);
                }

                /**
                * Mapping rules for all other fields from Book Element Set to DC
                *   > Identifier are amended with a description of their type.
                *   > All other Fields are mapped according to the rules defined in the array $mapping
                */

                elseif ($bookField == 'ISBN Print') {
                    $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => 'ISBN Print: ' . $bookElementText);
                    $db->insert('element texts', $dcElementValues);
                }
                elseif ($bookField == 'ISBN PDF') {
                   $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => 'ISBN PDF: ' . $bookElementText);
                    $db->insert('element texts', $dcElementValues);
                }
                elseif ($bookField == 'ISBN EPUB') {
                    $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => 'ISBN EPUB: ' . $bookElementText);
                    $db->insert('element texts', $dcElementValues);
                }
                elseif ($bookField == 'DOI') {
                    $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => 'DOI: ' . $bookElementText);
                    $db->insert('element texts', $dcElementValues);
                }
                elseif ($bookField == 'Series') {
                    $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => 'Series: ' . $bookElementText);
                    $db->insert('element texts', $dcElementValues);
                }
                else {
                    $dcElementValues = array(
                            'record_id' => $id, // 'record_id'
                            'record_type' => 'Item',
                            'element_id' => $dcID, // 'element_id'
                            'html' => $html, // 'html'
                            'text' => $bookElementText);
                    $db->insert('element texts', $dcElementValues);
                }
                
            }
        }
    }

    public function filterAdminItemsFormTabs($tabs, $args)
    {
        $ItemAdminOrder = array('Book' => '', 'Files' => '', 'Tags' => '', 'Item Type Metadata' => '', 'Dublin Core' => '');
        return (array_merge ($ItemAdminOrder, $tabs));
     //   return ($tabs);
    }


    /**
    * Checks if any field of the Book Element Set has been filled out. If yes, true is returned.
    */
    public function has_book_element_texts($item){

        $bookElementFields = array('Title', 'Subtitle', 'Author/Editor', 'Publisher', 'Year Published', 'Blurb', 
                                 'Keywords', 'ISBN Print', 'ISBN PDF', 'ISBN EPUB', 'DOI', 
                                 'Rights', 'Language', 'Type', 'Format');
        
        foreach ($bookElementFields as $bookElementField){
            $bookElementsTest = array($item->getElementTexts('Book', $bookElementField));
               if ($bookElementsTest[0] != NULL){
                    return true;
                }
            }
        return false;
    }
}
