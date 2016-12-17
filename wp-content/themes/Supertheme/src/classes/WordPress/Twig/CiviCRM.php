<?php
/**
 * Created by PhpStorm.
 * User: rpark
 * Date: 11/21/2016
 * Time: 1:42 AM
 */

namespace Supertheme\WordPress\Twig;


class CiviCRM
{
    public function convert_county_id($id) {
        return \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
            $id,
            'abbreviation'
        );
    }
}