<?php
/**
 * Simple Pages
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

require_once dirname(__FILE__) . '/helpers/SimplePageFunctions.php';

/**
 * Simple Pages plugin.
 */
class SimplePagesPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array('install', 'uninstall',
        'define_acl', 'define_routes', 'config_form', 'config',
        'html_purifier_form_submission');

    /**
     * @var array Filters for the plugin.
     */
    protected $_filters = array('admin_navigation_main',
        'public_navigation_main', 'search_record_types', 'page_caching_whitelist',
        'page_caching_blacklist_for_record',
	'api_resources', 'api_import_omeka_adapters');

    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'simple_pages_filter_page_content' => '0'
    );

    /**
     * Install the plugin.
     */
    public function hookInstall()
    {
        // Create the table.
        $db = $this->_db;
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->SimplePagesPage` (
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
        $page = new SimplePagesPage;
        $page->modified_by_user_id = current_user()->id;
        $page->created_by_user_id = current_user()->id;
        $page->is_published = 1;
        $page->parent_id = 0;
        $page->title = 'About';
        $page->slug = 'about';
        $page->text = '<p>Communication about content, requests and additions to this catalogue can be addressed to
“psych_datasets@lancaster.ac.uk"


The LUSTRE project has been developed by 
Dr John Towse (Department of Psychology, Lancaster University) [Maybe best not to have URL’s to web pages bacause these might change]
Dr Rob Davies (Department of Psychology, Lancaster University
Ben Gooding (School of Computing and Communications, Lancaster University)

Code development work for the project is hosted at: https://github.com/Ben2917/LUSTRE

Supported by a Teaching Development Grant from the Faculty of Science and Technology, Lancaster University. A previous version of this project was support by a CETL mini award from the Department of Maths and Statistics, Lancaster University
</p>'; // Reading this from a file would be better
        $page->save();

        $page = new SimplePagesPage;
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
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {        
        // Drop the table.
        $db = $this->_db;
        $sql = "DROP TABLE IF EXISTS `$db->SimplePagesPage`";
        $db->query($sql);

        $this->_uninstallOptions();
    }

    /**
     * Define the ACL.
     * 
     * @param Omeka_Acl
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        
        $indexResource = new Zend_Acl_Resource('SimplePages_Index');
        $pageResource = new Zend_Acl_Resource('SimplePages_Page');
        $acl->add($indexResource);
        $acl->add($pageResource);

        $acl->allow(array('super', 'admin'), array('SimplePages_Index', 'SimplePages_Page'));
        $acl->allow(null, 'SimplePages_Page', 'show');
        $acl->deny(null, 'SimplePages_Page', 'show-unpublished');
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
        $pages = get_db()->getTable('SimplePagesPage')->findAll();
        foreach ($pages as $page) {
            $router->addRoute(
                'simple_pages_show_page_' . $page->id, 
                new Zend_Controller_Router_Route(
                    $page->slug, 
                    array(
                        'module'       => 'simple-pages', 
                        'controller'   => 'page', 
                        'action'       => 'show', 
                        'id'           => $page->id
                    )
                )
            );
        }
    }

    /**
     * Display the plugin config form.
     */
    public function hookConfigForm()
    {
        require dirname(__FILE__) . '/config_form.php';
    }

    /**
     * Set the options from the config form input.
     */
    public function hookConfig()
    {
        set_option('simple_pages_filter_page_content', (int)(boolean)$_POST['simple_pages_filter_page_content']);
    }

    /**
     * Filter the 'text' field of the simple-pages form, but only if the 
     * 'simple_pages_filter_page_content' setting has been enabled from within the
     * configuration form.
     * 
     * @param array $args Hook args, contains:
     *  'request': Zend_Controller_Request_Http
     *  'purifier': HTMLPurifier
     */
    public function hookHtmlPurifierFormSubmission($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $purifier = $args['purifier'];

        // If we aren't editing or adding a page in SimplePages, don't do anything.
        if ($request->getModuleName() != 'simple-pages' or !in_array($request->getActionName(), array('edit', 'add'))) {
            return;
        }
        
        // Do not filter HTML for the request unless this configuration directive is on.
        if (!get_option('simple_pages_filter_page_content')) {
            return;
        }
        
        $post = $request->getPost();
        $post['text'] = $purifier->purify($post['text']); 
        $request->setPost($post);
    }

    /**
     * Add the Simple Pages link to the admin main navigation.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterAdminNavigationMain($nav)
    {

        console_log(url('simple-pages'));

        $nav[] = array(
            'label' => __('Simple Pages'),
            'uri' => url('simple-pages'),
            'resource' => 'SimplePages_Index',
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
        $navLinks = simple_pages_get_links_for_children_pages(0, 0, 'order', true);
        $nav = array_merge($nav, $navLinks);
        return $nav;
    }

    /**
     * Add SimplePagesPage as a searchable type.
     */
    public function filterSearchRecordTypes($recordTypes)
    {
        $recordTypes['SimplePagesPage'] = __('Simple Page');
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
        $pages = get_db()->getTable('SimplePagesPage')->findAll();
        foreach($pages as $page) {
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

        if ($record instanceof SimplePagesPage) {
            $page = $record;
            if ($action == 'update' || $action == 'delete') {
                $blacklist['/' . trim($page->slug, '/')] = array('cache'=>false);
            }
        }
            
        return $blacklist;
    }
    public function filterApiResources($apiResources)
    {
	$apiResources['simple_pages'] = array(
		'record_type' => 'SimplePagesPage',
		'actions'   => array('get','index'),
	);	
       return $apiResources;
    }
    
    public function filterApiImportOmekaAdapters($adapters, $args)
    {
        $simplePagesAdapter = new ApiImport_ResponseAdapter_Omeka_GenericAdapter(null, $args['endpointUri'], 'SimplePagesPage');
        $simplePagesAdapter->setService($args['omeka_service']);
        $simplePagesAdapter->setUserProperties(array('modified_by_user', 'created_by_user'));
        $adapters['simple_pages'] = $simplePagesAdapter;
        return $adapters;
    }
}
