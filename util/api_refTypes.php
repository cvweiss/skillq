<?php

require_once __DIR__ . "/../init.php";

$pheal = Util::getPheal();

$refTypes = $pheal->eveScope->RefTypes();
foreach ($refTypes->refTypes as $refType) {
    Db::execute(
      "replace into ccp_api_refTypes (refTypeID, refTypeName) values (:refTypeID, :refTypeName)",
      array(":refTypeID" => $refType->refTypeID, ":refTypeName" => $refType->refTypeName)
    );
}
