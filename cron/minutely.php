<?php

require_once "../init.php";

Db::execute("delete from skq_scopes where errorCount = 10 and lastErrorCode = 400");
