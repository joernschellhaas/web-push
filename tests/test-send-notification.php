<?php

require_once dirname(dirname(__FILE__)) . '/build/web-push.php';

class SendNotificationTest extends WP_UnitTestCase {
  function tearDown() {
    parent::tearDown();
    remove_all_filters('pre_http_request');
  }

  function test_send_webpush_notification_success() {
    $self = $this;
    add_filter('pre_http_request', function($url, $r) use ($self) {
      $self->assertTrue($r['headers']['TTL'] > 0);

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 201,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    }, 10, 2);

    $webPush = new WebPush();
    $webPush->addRecipient('endpoint', false, 'aKey', function($success) {
      $this->assertTrue($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_webpush_notification_success_no_key() {
    add_filter('pre_http_request', function() {
      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 201,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $webPush = new WebPush();
    $webPush->addRecipient('endpoint', false, '', function($success) {
      $this->assertTrue($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_webpush_notification_failure() {
    add_filter('pre_http_request', function() {
      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 400,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $webPush = new WebPush();
    $webPush->addRecipient('endpoint', false, 'aKey', function($success) {
      $this->assertFalse($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_gcm_notification_success() {
    $self = $this;
    add_filter('pre_http_request', function($url, $r) use ($self) {
      $self->assertEquals('key=aKey', $r['headers']['Authorization']);
      $self->assertEquals('application/json', $r['headers']['Content-Type']);
      $self->assertEquals(33, $r['headers']['Content-Length']);

      $data = json_decode($r['body']);
      $self->assertEquals(1, count($data->registration_ids));
      $self->assertEquals('endpoint', $data->registration_ids[0]);

      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 200,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    }, 10, 2);

    $webPush = new WebPush();
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) {
      $this->assertTrue($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_gcm_notification_failure() {
    add_filter('pre_http_request', function() {
      return array(
        'headers' => array(),
        'body' => '',
        'response' => array(
          'code' => 400,
        ),
        'cookies' => array(),
        'filename' => '',
      );
    });

    $webPush = new WebPush();
    $webPush->addRecipient('https://android.googleapis.com/gcm/send/endpoint', true, 'aKey', function($success) {
      $this->assertFalse($success);
    });
    $webPush->sendNotifications();
  }

  function test_send_notification_error() {
    add_filter('pre_http_request', function() {
      return new WP_Error('Error');
    });

    $webPush = new WebPush();
    $webPush->addRecipient('endpoint', false, 'aKey', function($success) {
      $this->assertTrue($success);
    });
    $webPush->sendNotifications();
  }
}

?>
