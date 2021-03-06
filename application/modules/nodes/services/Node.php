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

class Nodes_Service_Node
{
	protected $_destPath = null;

	protected $_authKeysCmd = '/opt/unwired/genconfig.sh';

	public function writeUci(Nodes_Model_Node $node)
	{
		$mac = $node->getMac();

		if (empty($mac) || $node->getStatus() == 'planning') {
			return true;
		}

		$view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')
														->view;

		$view->node = $node;
		$uci = $view->render('index/uci.template.sh');

		$path = $this->getDestPath() . '/' . str_replace(':','',$node->getMac()) . '.uci.sh';

		/**
		 * @todo Path check
		 */
		if (!file_exists($this->getDestPath())) {
			@mkdir($this->getDestPath(), 0777, true);
		}

		if (!@file_put_contents($path, $uci)) {
			return false;
		}

		return $this->_createAuthKeys($node);

		/**
		 * @todo Cleanup
		 */
	}

	public function getDestPath()
	{
		if (null === $this->_destPath) {
			$this->_destPath = APPLICATION_PATH . '/data/uci';
		}
		return $this->_destPath;
	}

	/**
	 * The createAuthKeys method creates the auth-key files by calling
	 * a shell script defined in $_authKeysCmd. It receives one parameter (MAC address).
	 *
	 * @return boolean true if the bashscript exited with successstatus,
	 *                 otherwise false.
	 */
	private function _createAuthKeys(Nodes_Model_Node $node) {

		// only call the command if we are on a linux-machine
		$os = php_uname('s');
		$isLinux = strcasecmp($os, 'linux') == 0;

		// only execute if on linux-distro
		if (!$isLinux) {
			return true;
		}

		$command = $this->_authKeysCmd . ' ' . str_replace(':','',$node->getMac());

		ob_start();
		system($command, $exitStatus);
		$output = ob_get_contents();
		ob_end_clean();

		if ($exitStatus != 0) {
			return false;
		}

		return true;
	}
}