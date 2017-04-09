<?php

namespace Drupal\newsletter2go\Helpers;

class Api {
    private $version = 4000;
    private static $instance = null;
    private $getParams;
    private $postParams;
    private $apikey;

    private function __construct()
    {
        
    }

    public static function getInstance()
    {
        return self::$instance ? : new Api();
    }

    public function processRequest($apikey, $getParams, $postParams)
    {
        $this->apikey = $apikey;
        if($apikey == null && isset($postParams['apikey'])){
            $this->apikey = $postParams['apikey'];
        }
        $this->getParams = $getParams;
        $this->postParams = $postParams;
        $result = array('success' => 1);

        if (!$this->checkApiKey()) {
            $result = ResponseHelper::generateErrorResponse('Invalid or missing API key!',ResponseHelper::ERRNO_PLUGIN_CREDENTIALS_WRONG);
        } else {
            switch ($this->postParams['action']) {
                case 'test':
                    $result['message'] = $this->test();
                    break;
                case 'getPost':
                    $post = $this->getPost();
                    if (!$post) {
                        $result = ResponseHelper::generateErrorResponse('Post with given id not found!',ResponseHelper::ERRNO_PLUGIN_OTHER);
                    }else{
                        $result = ResponseHelper::generateSuccessResponse(array('post' => $post));
                    }
                    break;
                case 'getPluginVersion':
                    $version = $this->getPluginVersion();
                    $result = ResponseHelper::generateSuccessResponse(array('version' => $version));
                    break;
                default:
                    $result = ResponseHelper::generateErrorResponse('Invalid action!',ResponseHelper::ERRNO_PLUGIN_OTHER);
                    break;
            }
        }

        drupal_json_output($result);
        drupal_exit();
    }

    protected function test()
    {
        return t('Connected');
    }

    protected function getPost()
    {
        if (empty($this->postParams['id']) && is_int($this->postParams['id'])) {
            return null;
        }
        
        $id = $this->postParams['id'];
        $query = db_select('node', 'n');
        $query->leftJoin('field_data_body', 'd', 'd.entity_id = n.nid');
        $query->leftJoin('users', 'u', 'u.uid = n.uid');
        $query->condition('n.nid', $id)
                ->condition('n.type', 'article');
        $query->addField('n', 'nid', 'itemId');
        $query->addField('n', 'title', 'title');
        $query->addField('n', 'created', 'date');
        $query->addField('u', 'name', 'author');
        $query->addField('d', 'body_value', 'description');
        $query->addField('d', 'body_summary', 'shortDescription');
        $result = $query->execute()->fetchAssoc();
        if (!$result) {
            return null;
        }
        
        $result['url'] = url('', array('absolute' => true));
        $result['link'] = 'node/' . $id;
        $result['date'] = date('Y-m-d H:i:s', $result['date']);
        $result['category'] = array();
        
        //tags
        $query = db_select('field_data_field_tags', 't');
        $query->innerJoin('taxonomy_term_data', 'dt', 't.field_tags_tid = dt.tid');
        $query->condition('t.entity_id', $id);
        $query->addField('dt', 'name', 'name');
        $result['tags'] = $query->execute()->fetchAll();
        foreach ($result['tags'] as &$tag) {
            $tag = $tag->name;
        }
        
        //images
        $query = db_select('field_data_field_image', 'fi');
        $query->innerJoin('file_managed', 'f', 'fi.field_image_fid = f.fid');
        $query->condition('fi.entity_id', $id);
        $query->addField('f', 'uri', 'uri');
        $result['images'] = $query->execute()->fetchAll();
        foreach ($result['images'] as &$image) {
            $image = file_create_url($image->uri);
        }
        
        return $result;
    }

    protected function checkApiKey()
    {
        return variable_get('newsletter2go_apikey') === $this->apikey;
    }

    protected function getPluginVersion(){
        return $this->version;
    }

}
