

/**
* LUSTRE Additional Meta Data
* @author Ben Gooding
*/

<?php 
    // add_plugin_hook("", "LUSTREMetaData"); // TODO: Chooses approriate hook from list
    
    class LUSTREMetaDataGenerator extends Zend_View_Helper_Abstract {
    
        // TODO: Add code to map custom meta data.
    
    }

    class LUSTREMetaData extends Omeka_Plugin_AbstractPlugin {
        
        protected $_hooks = array(
            'public_items_show', 'admin_items_show', 'public_items_browse_each', 
            'admin_items_browse_simple_each'
        );

       // TODO: Function to show span for the meta data.

    }
    
?>
