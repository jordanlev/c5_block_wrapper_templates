<?php defined('C5_EXECUTE') or die(_("Access Denied."));

class BlockWrapperTemplatesPackage extends Package {

	protected $pkgHandle = 'block_wrapper_templates';
	protected $pkgVersion = '1.0';
	protected $appVersionRequired = '5.6';

	public function getPackageDescription() {
		return t("Adds BlockWrapperTemplate support to C5 Areas");
    }

	public function getPackageName() {
		return t("Block Wrapper Templates");
	}

	public function install() {
		$pkg = parent::install();
	}
	
	public function on_start() {
		Loader::registerAutoload(array('Area' => array('model', 'area', $this->getPackageHandle())));
	}
}