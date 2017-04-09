<?php

namespace Drupal\newsletter2go\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\newsletter2go\Helpers\Api;
use Drupal\newsletter2go\Helpers\Callback;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class PageController.
 *
 * @package Drupal\newsletter2go\Controller
 */
class PageController extends ControllerBase {

  /**
   * Widgetpreview.
   *
   * @return string
   *   Return Hello string.
   */
  public function widgetPreview() {
    return [
      '#type' => 'markup',
      '#markup' => urldecode($_GET['widget']),
    ];
  }

  /**
   * Process instance authorization.
   */
  public function apiGo() {
    $instance = Api::getInstance();
    $result = $instance->processRequest($_SERVER['PHP_AUTH_USER'], $_GET, $_POST);
    return new JsonResponse($result);
  }

  /**
   * Process callback.
   */
  public function goCallback() {
    $instance = Callback::getInstance();
    $instance->processCallback($_POST);
  }


  /**
   * Request newsletter2go page.
   */
  function subscribe()
  {
    $notFound = false;
    $noValidEmail = false;
    $attributes = variable_get('newsletter2go_fields');
    $requiredFields = variable_get('newsletter2go_required');
    $texts = variable_get('newsletter2go_texts');
    $post = array();
    foreach ($attributes as $k => $v) {
      if (!empty($requiredFields[$k]) && empty($_POST[$k])) {
        $notFound = true;
        break;
      }

      if ($k == 'email') {
        if (!filter_var($_POST[$k], FILTER_VALIDATE_EMAIL)) {
          $noValidEmail = true;
        }
      }

      $post[$k] = $_POST[$k];
    }

    if ($notFound) {
      drupal_json_output(array('success' => 0, 'message' => $texts['failureRequired']));
      drupal_exit();
    }

    if ($noValidEmail) {
      drupal_json_output(array('success' => 0, 'message' => $texts['failureEmail']));
      drupal_exit();
    }

    $post['key'] = variable_get('newsletter2go_apikey');
    $post['doicode'] = variable_get('newsletter2go_doicode');
    $response = executeN2Go('create/recipient', $post);

    $result = array('success' => $response['success']);
    if (!$response) {
      $result['message'] = $texts['failureEmail'];
    } else {
      switch ($response['status']) {
        case 200:
          $result['message'] = $texts['success'];
          break;
        case 441:
          $result['message'] = $texts['failureSubsc'];
          break;
        case 434:
        case 429:
          $result['message'] = $texts['failureEmail'];
          break;
        default:
          $result['message'] = $texts['failureGeneral'];
          break;
      }
    }

    return new JsonResponse($result);
  }

  /**
   * This function sets widgetStyleConfig to default value
   */
  function resetStyles() {
    $style = $_POST['style'];
    variable_set('newsletter2go_widgetStyleConfig', $style);
    print 'success';
    drupal_exit();
  }

}
