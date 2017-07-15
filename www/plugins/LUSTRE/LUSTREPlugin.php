<?php

/**
 * LUSTRE Meta Data Plugin
 */
class LUSTREPlugin extends Omeka_Plugin_AbstractPlugin {

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
            'name' => 'Statistical Analysis Type',
            'description' => 'The type of statistical analysis used in the project'
        ),
        array(
            'name' => 'Sample Size'
        ));         
        insert_element_set($elementSetMetadata, $elements);
    }  

    /**
    * Uninstall the plugin
    */
    public function hookUninstall() {
        $db = get_db();
        $elementSet = get_db()->getTable('ElementSet')->findByName('LUSTRE');
        $elementSet->delete();
    }

    /** 
    * After saving an item with data in the LUSTRE Element Set values are automatically
    * mapped on the Dublin Core Element Set. Preexisting DC values are deleted
    */
    public function hookAfterSaveItem($args) {   
        $item = $args['record'];
        $id = $item['id'];
        $db = get_db();
        $DCElementSetIDSelect = $db -> select() -> from (array('omeka_element_sets'), 
            array('id')) -> where ('name = ?', 'Dublin Core');
        $DCElementSetID =  $db -> fetchOne($DCElementSetIDSelect);
        $DCElementIDsSelect = $db -> select() -> from (array('omeka_elements'), 
            array('id')) -> where ('element_set_id = ?', $DCElementSetID);
        $DCElementIDs= $db -> fetchCol($DCElementIDsSelect);

        /* Rules to map LUSTRE meta data to dublin core.
        */
        $mapping = array('Supervisor' => 'Identifier', 'Project Level' => 'Identifier',
            'Topic' => 'Type', 'Statistical Analysis Type' => 'Identifier', 
            'Sample Size' => 'Source');
        
        $lustreElementSetIDSelect = $db -> select() -> from (array('omeka_element_sets'), 
            array('id')) -> where ('name = ?', 'LUSTRE');
        $lustreElementSetID =  $db -> fetchOne($lustreElementSetIDSelect);

        foreach ($mapping as $lustreField => $DCField) {

            // TODO: Check fields for invalid input/reserved keywords

            $lustreElementTexts = $item->getElementTexts('LUSTRE', $lustreField);

            foreach ($lustreElementTexts as $lustreElementText){
                
                $dcElementIDSelect = $db -> select() -> from (array('omeka_elements'), array('id')) 
                    -> where ('element_set_id = ?', $DCElementSetID) 
                    -> where ('name = ?', $DCField);
                $dcID =  $db -> fetchOne($dcElementIDSelect);
                $lustreElementIDSelect = $db -> select() -> from (array('omeka_elements'), 
                    array('id')) -> where ('element_set_id = ?', $lustreElementSetID)
                    -> where ('name = ?', $lustreField);
                $lustreID =  $db -> fetchOne($lustreElementIDSelect);

                // Check if fields contain html
                $htmlSelect = $db -> select() -> from (array('omeka_element_texts'), 
                    array('html')) -> where ('element_id = ?', $lustreID)
                    -> where ('record_id = ?', $id) -> where ('text = ?', $lustreElementText);
                $html =  $db -> fetchOne($htmlSelect);
                
                /* EXPERIMENTAL */

                // Delete any previous LUSTRE elements in given field.
                $db->queryBlock('DELETE FROM omeka_element_texts WHERE text LIKE \'' 
                    . $lustreField . '%\'', ';');

                // $db->queryBlock('DELETE FROM omeka_element_texts WHERE record_id = ' 
                //    . $id, ';');

                $dcElementValues = array(
                    'record_id' => $id, 'record_type' => 'Item',
                    'element_id' => $dcID, 'html' => $html,
                    'text' => $lustreField . ': ' . $lustreElementText
                );
                $db->insert('element texts', $dcElementValues);

                /* EXPERIMNETAL END */
                              
            }
        }
    }

    public function filterAdminItemsFormTabs($tabs, $args) {
        $ItemAdminOrder = array('Dublin Core' => '', 'LUSTRE' => '', 'Files' => '', 
            'Tags' => '', 'Item Type Metadata' => '');
        return (array_merge ($ItemAdminOrder, $tabs));
    }


    /**
    * Returns true if any LUSTRE fields have been filled out. False otherwise.
    */
    public function lustreFieldsFilled($item){
        $lustreElementFields = array('Supervisor', 'Project Level', 'Topic', 
            'Statistical Analysis Type', 'Sample Size');
        foreach ($lustreElementFields as $lustreElementField){
            $lustreElementsTest = array($item->getElementTexts('LUSTRE', $lustreElementField));
            if ($lustreElementsTest[0] != NULL) {
                return true;
            }
        }
        return false;
    }
}
