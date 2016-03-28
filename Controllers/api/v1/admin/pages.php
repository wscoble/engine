<?php
/**
 * Minds Admin: Feature
 *
 * @version 1
 * @author Mark Harding
 *
 */
namespace Minds\Controllers\api\v1\admin;

use Minds\Core;
use Minds\Helpers;
use Minds\Entities;
use Minds\Interfaces;
use Minds\Api\Factory;

class pages implements Interfaces\Api, Interfaces\ApiIgnorePam
{
    /**
     *
     */
    public function get($pages)
    {
        if (isset($pages[0])) {
            try {
                $page = (new Entities\Page())
                    ->loadFromGuid($pages[0])
                    ->export();
                $response = $page;
            } catch (\Exception $e) {
                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        } else {
            $pages = Core\Pages\Manager::_()->getPages();
            $response = [
                'pages' => Factory::exportable($pages)
            ];
        }

        return Factory::response($response);
    }

    /**
     * @param array $pages
     */
    public function post($pages)
    {
        if (!Core\Session::isAdmin()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not authorized'
            ]);
        }

        if (!isset($_POST['path']) || !$_POST['path']) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must supply a path'
            ]);
        }

        switch($pages[1]){
            case "header":
              try{
                $page = (new Entities\Page())->loadFromGuid($pages[0]);
              } catch (\Exception $e){
                return Factory::response([
                  'status' => 'error',
                  'message' => 'page not found'
                ]);
              }

              if (is_uploaded_file($_FILES['file']['tmp_name'])) {
                  $resized = get_resized_image_from_uploaded_file('file', 2000);
                  $path = $pages[0];
                  $filepath = Core\Config::_()->dataroot . "page_banners/" . $page->getPath() . ".jpg";
                  @mkdir(Core\Config::_()->dataroot . "page_banners", 0777, true);

                  $f = fopen($filepath, "w+b");
                  fwrite($f, $resized);
                  fclose($f);
              }

              $page->setHeader(true)
                ->setHeaderTop($_POST['headerTop']);

              $saved = $page->save();

              return Factory::response(compact('saved'));
              break;
            case "update":
            case "add":
            default:
              $subtype = 'page';

              if (isset($_POST['subtype'])) {
                $subtype = $_POST['subtype'];
              }

              $page = (new Entities\Page())
                ->setTitle($_POST['title'])
                ->setBody($_POST['body'])
                ->setMenuContainer($_POST['menuContainer'])
                ->setPath($_POST['path'])
                ->setSubtype($subtype);

              $saved = (bool) $page->save();

              return Factory::response(compact('saved'));
        }

        return Factory::response([]);
    }

    /**
     * @param array $pages
     */
    public function put($pages)
    {
    }

    /**
     * @param array $pages
     */
    public function delete($pages)
    {
        if (!Core\Session::isAdmin()) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You are not authorized'
            ]);
        }

        // Workaround for links
        // TODO: ^^ Maybe make it permanent (not use $pages), or fix services/client.ts to accept data as JSON-encoded
        if (isset($_GET['path'])) {
          $path = $_GET['path'];
        } else {
          $path = $pages[0];
        }

        if (!$path) {
            return Factory::response([
                'status' => 'error',
                'message' => 'You must supply a path'
            ]);
        }

        $response = [];
        try {
            $page = (new Entities\Page())
                ->loadFromGuid($path)
                ->delete();
        } catch (\Exception $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
        return Factory::response($response);
    }
}
