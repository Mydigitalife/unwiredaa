<?php
class Users_Form_ForgotPassword extends Unwired_Form
{

    public function init()
    {
        parent::init();

		$this->addElement('text', 'email', array('label' => 'users_index_forgot_form_email',
													'required' => true,
													'class' => 'span-5',
													'validators' => array('email' => array('validator' => 'EmailAddress'),
		                                                                  'dbrec' => array('validator' => 'Db_RecordExists',
																				    	'options' => array(
																								'table' => 'admin_user',
																						        'field' => 'email',
		                                                                                        'message' => 'hdshashsdh'
																					    )))));

		$bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');

		$usersOptions = $bootstrap->getOption('users');

		if (isset($usersOptions['recaptcha'])) {
		    $serviceRecaptcha = new Zend_Service_ReCaptcha($usersOptions['recaptcha']['public'],
		                                                   $usersOptions['recaptcha']['private']);

            $captchaAdapter = new Zend_Captcha_ReCaptcha();
            $captchaAdapter->setService($serviceRecaptcha);
		} else {
		    $captchaAdapter = new Zend_Captcha_Image();

		    $captchaAdapter->setImgDir('./data/captcha/')
		                   ->setImgUrl('/data/captcha/')
		                   ->setFont(PUBLIC_PATH . '/data/captcha/arcade.ttf');
		}

		$this->addElement('captcha', 'recaptcha', array('label' => 'users_index_forgot_form_captcha',
													'required' => true,
		                                            'captcha' => $captchaAdapter,
													'class' => 'span-5 hidden'
		                                            ));


		$this->addElement('submit', 'form_element_submit', array('label' => 'users_index_forgot_form_send',
     														 	 'tabindex' => 20,
																 'class'	=> 'button',
															 	 'decorators' => array('ViewHelper',
																				 		array(array('span' => 'HtmlTag'),
						            				   									 	   array ('tag' => 'span',
																		   				 		 	  'class' => 'button green')),
        				   									 	   )));

    }
}