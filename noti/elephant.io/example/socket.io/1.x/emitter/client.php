<?php
/**
 * This file is part of the Elephant.io package
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Wisembly
 * @license   http://www.opensource.org/licenses/MIT-License MIT License
 */

use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

require __DIR__ . '/../../../../vendor/autoload.php';

ini_set('display_errors',1);

//$client = new Client(new Version1X('http://localhost:1337'));
$userId = 6;
$client = new Client(new Version1X('https://chat.vdomax.com:1314'));
$client->initialize();
$client->emit('Authenticate', ['userId' => $userId]);
// $client->on('Authenticate:Success', function($msg){
// 	print_r($msg);
// });
//$client->emit('broadcast', ['foo' => 'bar']);
$client->emit('notify', $_GET);
$client->close();
