<?php
namespace PWTC\WordPress\Twig;

class CiviCRM
{
    public function convert_county_id($id) {
        return \CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StateProvince',
            $id,
            'abbreviation'
        );
    }
}