<?php
namespace PWTC\WordPress\Twig;

/**
 * Class WordPressExtension
 * @package Supertheme\WordPress\Twig
 */
class Theme
{
    /**
     * @var ACF
     */
    protected $ACF;

    /**
     * @var WordPress
     */
    protected $wordPress;

    /**
     * @var WordPress
     */
    protected $civicrm;


    /**
     * SuperTheme constructor.
     * @param ACF $acf
     * @param WordPress $wordpress
     */
    public function __construct(ACF $acf, WordPress $wordpress, CiviCRM $civicrm)
    {
        $this->ACF = $acf;
        $this->wordPress = $wordpress;
        $this->civicrm = $civicrm;
    }

    /**
     * @return ACF
     */
    public function getACF()
    {
        return $this->ACF;
    }

    /**
     * @return WordPress
     */
    public function getWordPress()
    {
        return $this->wordPress;
    }
    
    public function getCiviCRM() {
        return $this->civicrm;
    }

}