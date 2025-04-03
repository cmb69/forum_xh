<?php

const CMSIMPLE_XH_VERSION = "CMSimple_XH 1.8";
const CMSIMPLE_URL = "http://example.com/index.php";
const CMSIMPLE_ROOT = "/";
const FORUM_VERSION = "1.0beta5";

require_once './vendor/autoload.php';

require_once "../../cmsimple/classes/Mail.php";
require_once '../../cmsimple/functions.php';

require_once "../plib/classes/Codec.php";
require_once "../plib/classes/CsrfProtector.php";
require_once "../plib/classes/Document.php";
require_once "../plib/classes/DocumentStore.php";
require_once "../plib/classes/Random.php";
require_once "../plib/classes/Request.php";
require_once "../plib/classes/Response.php";
require_once "../plib/classes/SystemChecker.php";
require_once "../plib/classes/Url.php";
require_once "../plib/classes/View.php";
require_once "../plib/classes/FakeRequest.php";
require_once "../plib/classes/FakeSystemChecker.php";

spl_autoload_register(function (string $className) {
    $parts = explode("\\", $className);
    if ($parts[0] !== "Forum") {
        return;
    }
    if (count($parts) === 3) {
        $parts[1] = strtolower($parts[1]);
    }
    $filename = implode("/", array_slice($parts, 1)) . ".php";
    if (is_readable("./classes/$filename")) {
        include_once "./classes/$filename";
    } elseif (is_readable("./tests/$filename")) {
        include_once "./tests/$filename";
    }
});
