<?php



require_once dirname(__FILE__) . '/helpers/LUSTREFunctions.php';

/**
 * LUSTRE Meta Data Plugin
 * Based on the BookCore plugin: https://github.com/hybrid-publishing-lab/BookCore
 * Covered under apache 2.0 license: http://www.apache.org/licenses/LICENSE-2.0.html
 * and the SimplePages plugin:
 * Covered under the **** license.
 * Modifications made by Ben Gooding
 */
class LUSTREPlugin extends Omeka_Plugin_AbstractPlugin {

    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('install', 'uninstall', 'define_acl', 'define_routes', 'html_purifier_form_submission', 'after_save_item');
    
    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_items_form_tabs', 'admin_navigation_main',
        'public_navigation_main', 'search_record_types', 'page_caching_whitelist',
        'page_caching_blacklist_for_record',
	'api_resources', 'api_import_omeka_adapters');
    
    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'LUSTRE_filter_page_content' => '0'
    );
    
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
        ,
        array(
            'name' => 'Statistical Analysis Type',
            'description' => 'The type of statistical analysis used in the project'
        ),
        array(
            'name' => 'Sample Size'
        ));         
        insert_element_set($elementSetMetadata, $elements);
        
        // PAGES

        // Create the table.
        $db = $this->_db;
        console_log($db->LUSTREPage);
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->LUSTREPage` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `modified_by_user_id` int(10) unsigned NOT NULL,
          `created_by_user_id` int(10) unsigned NOT NULL,
          `is_published` tinyint(1) NOT NULL,
          `title` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `slug` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `text` mediumtext COLLATE utf8_unicode_ci,
          `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `inserted` timestamp NOT NULL DEFAULT '2000-01-01 00:00:00',
          `order` int(10) unsigned NOT NULL,
          `parent_id` int(10) unsigned NOT NULL,
          `template` tinytext COLLATE utf8_unicode_ci NOT NULL,
          `use_tiny_mce` tinyint(1) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `is_published` (`is_published`),
          KEY `inserted` (`inserted`),
          KEY `updated` (`updated`),
          KEY `created_by_user_id` (`created_by_user_id`),
          KEY `modified_by_user_id` (`modified_by_user_id`),
          KEY `order` (`order`),
          KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);
        
        // Save an example page.
        $page = new LUSTREPage;
        $page->modified_by_user_id = current_user()->id;
        $page->created_by_user_id = current_user()->id;
        $page->is_published = 1;
        $page->parent_id = 0;
        $page->title = 'About';
        $page->slug = 'about';
        $page->text = '<p>Communication about content, requests and additions to this catalogue can be addressed to
“psych_datasets@lancaster.ac.uk"


The LUSTRE project has been developed by 
Dr John Towse (Department of Psychology, Lancaster University) </br>
Dr Rob Davies (Department of Psychology, Lancaster University  </br>
Ben Gooding (School of Computing and Communications, Lancaster University) </br>

Code development work for the project is hosted at: https://github.com/Ben2917/LUSTRE </br>

Supported by a Teaching Development Grant from the Faculty of Science and Technology, Lancaster University. A previous version of this project was support by a CETL mini award from the Department of Maths and Statistics, Lancaster University
</p>'; // Reading this from a file would be better
        $page->save();

        $page = new LUSTREPage;
        $page->modified_by_user_id = current_user()->id;
        $page->created_by_user_id = current_user()->id;
        $page->is_published = 1;
        $page->parent_id = 0;
        $page->title = 'More Info and Contact';
        $page->slug = 'contact';
        $page->text = '<p>Welcome to LUSTRE. LUSTRE stands for Lancaster University STatistics REsources, and is an online catalog and repository designed to encourage best practice in data management and open data for students in Psychology and related disciplines. LUSTRE holds project information and digital resources.

LUSTRE has been designed to meet several teaching and research aims:

a) LUSTRE provides a platform to train students in data management and encourage open data standards. We believe that in order to foster open data practices and illustrate the benefits of good data stewardship, it is vital that students have experience and training in data cataloguing. LUSTRE helps bring data management into a teaching curriculum and highlight the benefits that this can bring.

b) LUSTRE increases the availability and functionality of student projects by providing an online catalog. We hope to improve quantitative analytic skills among students, through a forum that organises project data. By curating projects, it becomes easier to envisage how data that fuel them cannot be recycled and used in teaching. That is, to the extent that the teaching of analytic skills and approaches can be embedded in real world data, peer-created and managed, we hope to increase engagement in data science skills.

c) LUSTRE offers a project repository that acts as an informational resource that can be interrogated and used to by students to learn about past projects pics and details -  what projects have been attempted, who supervised them, what was undertaken, and how. All of this information can help students make better informed choice about their own project scope.

LUSTRE builds on the omeka platform providing a free, flexible, and open source web-publishing platform for the display of digital collections and exhibitions</p>';
        $page->save();

        $this->_installOptions();

    }  

    /**
    * Uninstall the plugin
    */
    public function hookUninstall() {
        $db = get_db();
        $elementSet = get_db()->getTable('ElementSet')->findByName('LUSTRE');
        $elementSet->delete();
        
        // Drop the table.
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->LUSTREPage`";
        $db->query($sql);

        $this->_uninstallOptions();
        
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
    
    /**
     * MORE PAGES FUNCTIONS
     * /
    
    /**
     * Define the ACL.
     * 
     * @param Omeka_Acl
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        
        $indexResource = new Zend_Acl_Resource('LUSTRE_Index');
        $pageResource = new Zend_Acl_Resource('LUSTRE_Page');
        $acl->add($indexResource);
        $acl->add($pageResource);

        $acl->allow(array('super', 'admin'), array('LUSTRE_Index', 'LUSTRE_Page'));
        $acl->allow(null, 'LUSTRE_Page', 'show');
        $acl->deny(null, 'LUSTRE_Page', 'show-unpublished');
    }

    /**
     * Add the routes for accessing simple pages by slug.
     * 
     * @param Zend_Controller_Router_Rewrite $router
     */
    public function hookDefineRoutes($args)
    {
        // Don't add these routes on the admin side to avoid conflicts.
        if (is_admin_theme()) {
            return;
        }

        $router = $args['router'];

        // Add custom routes based on the page slug.
        $pages = get_db()->getTable('LUSTREPage')->findAll();
        foreach ($pages as $page) {
            $router->addRoute(
                'LUSTRE_show_page_' . $page->id, 
                new Zend_Controller_Router_Route(
                    $page->slug, 
                    array(
                        'module'       => 'LUSTRE', 
                        'controller'   => 'page', 
                        'action'       => 'show', 
                        'id'           => $page->id
                    )
                )
            );
        }
    }

    
    /**
     * Add the Edit Contact link to the admin main navigation.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterAdminNavigationMain($nav)
    {
        console_log(url('edit-contact'));

        $nav[] = array(
            'label' => __('Edit Contact'),
            'uri' => url('edit-contact'),
            'resource' => 'LUSTRE_Index',
            'privilege' => 'browse'
        );
        return $nav;
    }

    /**
     * Add the pages to the public main navigation options.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterPublicNavigationMain($nav)
    {
        $navLinks = LUSTRE_get_links_for_children_pages(0, 0, 'order', true);
        $nav = array_merge($nav, $navLinks);
        return $nav;
    }

    /**
     * Add SimplePagesPage as a searchable type.
     */
    public function filterSearchRecordTypes($recordTypes)
    {
        $recordTypes['LUSTREPage'] = __('LUSTRE Page');
        return $recordTypes;
    }

    /**
     * Specify the default list of urls to whitelist
     * 
     * @param $whitelist array An associative array urls to whitelist, 
     * where the key is a regular expression of relative urls to whitelist 
     * and the value is an array of Zend_Cache front end settings
     * @return array The whitelist
     */
    public function filterPageCachingWhitelist($whitelist)
    {
        // Add custom routes based on the page slug.
        $pages = get_db()->getTable('LUSTREPage')->findAll();
        foreach($pages as $page) {
            console_log("Page whitelisted");
            $whitelist['/' . trim($page->slug, '/')] = array('cache'=>true);
        }
            
        return $whitelist;
    }

    /**
     * Add pages to the blacklist
     * 
     * @param $blacklist array An associative array urls to blacklist, 
     * where the key is a regular expression of relative urls to blacklist 
     * and the value is an array of Zend_Cache front end settings
     * @param $record
     * @param $args Filter arguments. contains:
     * - record: the record
     * - action: the action
     * @return array The blacklist
     */
    public function filterPageCachingBlacklistForRecord($blacklist, $args)
    {
        $record = $args['record'];
        $action = $args['action'];

        if ($record instanceof LUSTREPage) {
            $page = $record;
            if ($action == 'update' || $action == 'delete') {
                $blacklist['/' . trim($page->slug, '/')] = array('cache'=>false);
            }
        }
            
        return $blacklist;
    }
    public function filterApiResources($apiResources)
    {
	$apiResources['LUSTRE'] = array(
		'record_type' => 'LUSTREPage',
		'actions'   => array('get','index'),
	);	
       return $apiResources;
    }
    
    public function filterApiImportOmekaAdapters($adapters, $args)
    {
        $LUSTREAdapter = new ApiImport_ResponseAdapter_Omeka_GenericAdapter(null, $args['endpointUri'], 'LUSTREPage');
        $LUSTREAdapter->setService($args['omeka_service']);
        $LUSTREAdapter->setUserProperties(array('modified_by_user', 'created_by_user'));
        $adapters['LUSTRE'] = $LUSTREAdapter;
        return $adapters;
    }
    
}
