<?php

const CMSIMPLE_URL = "http://example.com/index.php";

const FORUM_VERSION = "1.0beta5";

require_once './vendor/autoload.php';

require_once "../../cmsimple/classes/CSRFProtection.php";
require_once '../../cmsimple/functions.php';

require_once "../fa/classes/RequireCommand.php";

require_once "./classes/value/Comment.php";
require_once "./classes/value/Topic.php";

require_once "./classes/infra/Authorizer.php";
require_once "./classes/infra/DateFormatter.php";
require_once "./classes/infra/Mailer.php";
require_once "./classes/infra/View.php";
require_once "./classes/infra/SystemChecker.php";

require_once './classes/BBCode.php';
require_once './classes/Contents.php';
require_once "./classes/InfoController.php";
require_once "./classes/MainController.php";
require_once "./classes/Response.php";
require_once "./classes/Url.php";
