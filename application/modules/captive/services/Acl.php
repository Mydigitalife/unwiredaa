<?php
/**
* Unwired AA GUI
*
* Author & Copyright (c) 2011 Unwired Networks GmbH
* alexander.szlezak@unwired.at
*
* Licensed under the terms of the Affero Gnu Public License version 3
* (AGPLv3 - http://www.gnu.org/licenses/agpl.html) or our proprietory
* license available at http://www.unwired.at/license.html
*/

class Captive_Service_Acl implements Zend_Acl_Assert_Interface
{
	/* (non-PHPdoc)
     * @see Zend_Acl_Assert_Interface::assert()
     */
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null,
                           Zend_Acl_Resource_Interface $resource = null, $privilege = null)
    {
        if (!$resource instanceof Captive_Model_Content) {
            return true;
        }

        if ($privilege !== 'edit' && $privilege !== 'delete') {
            return true;
        }

        /**
         * Content block belongs to splashpage and role does not have access to edit splashpage
         */
        if ($resource->getSplashId() && !$acl->isAllowed($role, 'captive_splashpage', 'edit')) {
            return false;
        }

        /**
         * Content block belongs to template and role does not have access to edit templates
         */
        if (!$resource->getSplashId() && !$acl->isAllowed($role, 'captive_template', 'edit')) {
            return false;
        }

        /**
         * Content block is not editable and we need to check for 'special' permission
         */
        if (((!$resource->isEditable() && $resource->getSplashId()) || $resource->isRestricted()) && !$acl->isAllowed($role, $resource, 'special')) {
            return false;
        }

        return true;
    }


}