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

class Unwired_Form extends Zend_Form
{
    protected $_entity = null;

	public function init()
	{
		parent::init();

		$this->addPrefixPath('Unwired_Form_', 'Unwired/Form');

		$this->addElementPrefixPath('Unwired_Validate_', 'Unwired/Validate', Zend_Form_Element::VALIDATE);

		$this->setDecorators(array('FormElements', 'Form'));

		$this->setElementDecorators(array(
										'element' => array('decorator' => 'ViewHelper'),
							        	'label' => array('decorator' => 'Label',
							            				 'options' => array('optionalSuffix' => ':',
							                								'requiredSuffix' => ' * :',
																			'placement' => 'prepend')
														),
										'errors' => 'errors',
										'htmltag' => array('decorator' => 'HtmlTag',
							            				   'options' => array ('tag' => 'div',
																			   'class' => 'formelement'))
										));
	}

	/**
	 * Get entity instance
	 *
	 * @return Unwired_Model_Generic
	 */
	public function getEntity()
	{
	    return $this->_entity;
	}

	/**
	 * Set entity instance
	 *
	 * @param Unwired_Model_Generic $entity
	 * @return Unwired_Form
	 */
	public function setEntity(Unwired_Model_Generic $entity = null)
	{
	    $this->_entity = $entity;
	    return $this;
	}
}