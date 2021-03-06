<?php

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;
use Sabre\HTTP;

require_once 'Sabre/DAV/PartialUpdate/FileMock.php';

class PluginTest extends \Sabre\DAVServerTest {

    protected $node;
    protected $plugin;

    public function setUp() {

        $this->node = new FileMock();
        $this->tree[] = $this->node;

        parent::setUp();

        $this->plugin = new Plugin();
        $this->server->addPlugin($this->plugin);



    }

    public function testInit() {

        $this->assertEquals('partialupdate', $this->plugin->getPluginName());
        $this->assertEquals(array('sabredav-partialupdate'), $this->plugin->getFeatures());
        $this->assertEquals(array(
            'PATCH'
        ), $this->plugin->getHTTPMethods('partial'));
        $this->assertEquals(array(
        ), $this->plugin->getHTTPMethods(''));

    }

    public function testPatchNoRange() {

        $this->node->put('00000000');
        $request = HTTP\Request::createFromServerArray(array(
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI'    => '/partial',
        ));
        $response = $this->request($request);

        $this->assertEquals('400 Bad request', $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchNotSupported() {

        $this->node->put('00000000');
        $request = HTTP\Request::createFromServerArray(array(
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI'    => '/',
            'X_UPDATE_RANGE' => '3-4',

        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals('405 Method Not Allowed', $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchNoContentType() {

        $this->node->put('00000000');
        $request = HTTP\Request::createFromServerArray(array(
            'REQUEST_METHOD'      => 'PATCH',
            'REQUEST_URI'         => '/partial',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-4',

        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals('415 Unsupported Media Type', $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchBadRange() {

        $this->node->put('00000000');
        $request = HTTP\Request::createFromServerArray(array(
            'REQUEST_METHOD'      => 'PATCH',
            'REQUEST_URI'         => '/partial',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-4',
            'HTTP_CONTENT_TYPE'   => 'application/x-sabredav-partialupdate',
        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals('416 Requested Range Not Satisfiable', $response->status, 'Full response body:' . $response->body);

    }

    public function testPatchSuccess() {

        $this->node->put('00000000');
        $request = HTTP\Request::createFromServerArray(array(
            'REQUEST_METHOD'      => 'PATCH',
            'REQUEST_URI'         => '/partial',
            'HTTP_X_UPDATE_RANGE' => 'bytes=3-5',
            'HTTP_CONTENT_TYPE'   => 'application/x-sabredav-partialupdate',
            'HTTP_CONTENT_LENGTH' => 3,
        ));
        $request->setBody(
            '111'
        );
        $response = $this->request($request);

        $this->assertEquals('204 No Content', $response->status, 'Full response body:' . $response->body);
        $this->assertEquals('00111000', $this->node->get());

    }

}
