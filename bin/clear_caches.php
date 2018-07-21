<?php
declare(strict_types=1);

require '_bin_autoload.php';

\exec("rm " . SOATOK_ROOT . '/data/markdown/*/*/*/*.cache');
\exec("rm " . SOATOK_ROOT . '/data/markdown/*/*/*.cache');
\exec("rm " . SOATOK_ROOT . '/data/markdown/*/*.cache');
\exec("rm " . SOATOK_ROOT . '/data/markdown/*.cache');
