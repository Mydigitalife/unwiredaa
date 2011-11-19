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

/**
 * Splash page form
 * @author B. Krastev <bkrastev@web-teh.net>
 */
class Captive_Form_SplashPage extends Unwired_Form
{
	public function init()
	{
		parent::init();

		$mapperLanguages = new Captive_Model_Mapper_Language();
		
		$this->addElement('text', 'title', array('label' => 'captive_index_edit_form_title',
												'required' => true,
												'validators' => array('len' => array('validator' => 'StringLength',
																				     'options' => array('min' => 2)),
																	  'db' => array('validator' => 'Db_NoRecordExists',
																				    'options' => array( 'adapter' => $mapperLanguages->getDbTable()->getAdapter(),
																								'table' => 'splash_page',
																						        'field' => 'title'
																					)))));

		$this->addElement('submit', 'form_element_submit', array('label' => 'captive_index_edit_form_save',
	 														 	 'tabindex' => 20,
																 'class'	=> 'button',
															 	 'decorators' => array('ViewHelper',
																				 		array(array('span' => 'HtmlTag'),
						            				   									 	   array ('tag' => 'span',
																		   				 		 	  'class' => 'button green')),
																						)));
		$this->addElement('href', 'form_element_cancel', array('label' => 'captive_index_edit_form_cancel',
	 														 	 'tabindex' => 20,
																 'href' => (isset($this->getView()->refererUrl)) ?
																					$this->getView()->refererUrl : null,
																 'data' => array(
																				'params' => array('module' => 'captive',
																					  			  'controller' => 'index',
																					  			  'action' => 'index'),
																				'route' => 'default',
																				'reset' => true
																			),
															 	 'decorators' => array('ViewHelper',
																				 		array(array('span' => 'HtmlTag'),
						            				   									 	   array ('tag' => 'span',
																		   				 		 	  'class' => 'button blue')),
																						)));
        $this->addDisplayGroup(array('title'), 'pagedata');

		$this->addDisplayGroup(array('form_element_submit', 'form_element_cancel'),
							   'formbuttons');

	    $this->setDisplayGroupDecorators(array('FormElements',
		   							     	   'HtmlTag' => array('decorator' => 'HtmlTag',
	    														  'options' => array ('tag' => 'div',
													 	     						  'class' => 'span-9'))));
	    $this->getDisplayGroup('formbuttons')
	    				 ->setDecorators(array('FormElements',
		   							     	   'HtmlTag' => array('decorator' => 'HtmlTag',
	    														  'options' => array ('tag' => 'div',
													 	     						  'class' => 'buttons span-18'))));
	}

	public function isValid($data)
	{
	    $entity = $this->getEntity();

	    $exclude = '';

	    if ($entity && $entity->getSplashId()) {
	        $exclude = 'splash_id != ' . $entity->getSplashId();
	    }

	    $this->getElement('title')->getValidator('Db_NoRecordExists')
	                                    ->setExclude($exclude);

	    return parent::isValid($data);
	}
}