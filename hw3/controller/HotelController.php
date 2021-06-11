<?php
require_once("model/UserDB.php");
require_once("ViewHelper.php");
require_once("model/HotelDB.php");
require_once("model/User.php");

class HotelController {
    public static function get() {
        if(User::isLoggedIn() && User::isAdmin()) {
            $hid = User::getHid();
            ViewHelper::render("view/hotel.php", [
                "rooms" => RoomDB::getHotelRooms($hid),
                "hotelName" => HotelDB::getName($hid)
            ]);
        }
        else {
            ViewHelper::redirect(BASE_URL . "room");
        }
    }

    public static function addRoom() {
        if (User::isLoggedIn() && User::isAdmin()) {
            $rules = [
                "type" => FILTER_SANITIZE_SPECIAL_CHARS,
                "name" => [
                    "filter" => FILTER_VALIDATE_REGEXP,
                    "options" => ["regexp" => "/^[ a-zA-ZšđčćžŠĐČĆŽ\.\-]+$/"]
                ],
                "price" => [
                    "filter" => FILTER_CALLBACK,
                    "options" => function ($value) { return (is_numeric($value) && $value >= 0) ? floatval($value) : false; }
                ]
             ];

             $data = filter_input_array(INPUT_POST, $rules);

             $errors["type"] = empty($data["type"]) ? "Enter a valid type" : "";
             $errors["name"] = $data["name"] === false ? "Name must much the requested format" : "";
             $errors["price"] = $data["price"] === false ? "Price must be a postive integer" : "";

             $isDataValid = true;
             foreach($errors as $error) {
                $isDataValid = $isDataValid && empty($error);
             }

            if($isDataValid) {
                $hid = User::getHid();
                RoomDB::insert($data["type"], $data["name"], $hid ,$data["price"]);
                //print_r(array_keys($_FILES));
               // ViewHelper::redirect(BASE_URL . "hotel");
                //die;

                if(self::uploadImage()) {
                    ViewHelper::redirect(BASE_URL . "hotel");
                }
                else {
                    $errors["fileUpload"] = "File is too large or is not a picture";
                    $tempRid = RoomDB::getLastRID();
                    RoomDB::deleteRoom($tempRid);
                    self::showAddForm($errors);
                }

            }
            else {
                self::showAddForm($errors);
            }
        }
        else {
            ViewHelper::redirect(BASE_URL . "room");
        }
    }

    public static function showAddForm($errors, $variables = array("type" => "", "name" => "",
        "price" => "")) {
            $variables["errors"] = $errors;
        ViewHelper::render("view/room-add.php", $variables);
    }

    public static function index() {
        if (User::isLoggedIn() && User::isAdmin()) {
            if (isset($_GET["rid"]) && self::containsRoom($_SESSION["user"]["hid"], $_GET["rid"])) {
                    $hid = User::getHid();
                    ViewHelper::render("view/room-hotel-detail.php", [
                        "room" => RoomDB::getRoom($_GET["rid"]),
                        "hotel" => HotelDB::getName($hid),
                        "reservations" => RoomDB::getReservations($_GET["rid"])]);
            } else {
                ViewHelper::redirect(BASE_URL . "hotel");
            }
        }
        else {
            ViewHelper::redirect(BASE_URL . "room");
        }
    }

    public static function uploadImage() {
        $target_dir = "static/images/";
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        $temp = $_FILES['fileToUpload']['tmp_name'];
        $ext = pathinfo($temp, PATHINFO_EXTENSION);
        $target_file_tmp = $target_dir . basename($_FILES["fileToUpload"]["tmp_name"]);
        $target_file = $target_dir . RoomDB::getLastRID();
       // var_dump(strtolower(pathinfo($target_file_tmp), PATHINFO_EXTENSION));

        $uploadOk = 1;

        // Check if image file is a actual image or fake image
        //$check = getimagesize($_POST["fileToUpload"]["tmp_name"]);
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

        if($check !== false) {
          //  echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
           // echo "File is not an image.";
            //return false;
            $uploadOk = 0;
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            // echo "Sorry, file already exists.";
            $uploadOk = 0;
           // return false;
        }

        // Check file size
        if ($_FILES["fileToUpload"]["size"] > 50000000) {
            echo "Sorry, your file is too large.";
        
            $uploadOk = 0;
            //return false;
        }

        // Allow certain file formats

      //  if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
      //  && $imageFileType != "gif" ) {

            //ViewHelper::redirect(BASE_URL . "room");
            //ViewHelper::render(BASE_URL . $image
       // echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
       //      $uploadOk = 0;
       // }


        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            return false;
            echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
        } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.";
            return true;
        } else {
            //echo "Sorry, there was an error uploading your file.";
            return false;
            }
        }
        return true;

    }

    public static function containsRoom($hid ,$rid) {
        return HotelDB::containsRoom($hid, $rid);
    }

    public static function editRoom() {
        $rules = [
            "typeOfRoom" => FILTER_SANITIZE_SPECIAL_CHARS,
            "name" => [
                "filter" => FILTER_VALIDATE_REGEXP,
                "options" => ["regexp" => "/^[ a-zA-ZšđčćžŠĐČĆŽ\.\-]+$/"]
            ],
            "price" => [
                "filter" => FILTER_CALLBACK,
                "options" => function ($value) { return (is_numeric($value) && $value > 0) ? floatval($value) : false; }
            ],
            "rid" => [
                "filter" => FILTER_VALIDATE_INT,
                "options" => ["min_range" => 0]
            ]
         ];
         $data = filter_input_array(INPUT_POST, $rules);

         $errors["typeOfRoom"] = empty($data["typeOfRoom"]) ? "Enter a valid type" : "";
         $errors["name"] = $data["name"] === false ? "Name must much the requested format" : "";
         $errors["price"] = $data["price"] === false ? "Price must be a postive integer" : "";
         $errors["rid"] = $data["rid"] === false ? "rid must be non-negative integer" : "";

         $isDataValid = true;
         foreach($errors as $error) {
            $isDataValid = $isDataValid && empty($error);
         }
        /*

        $validData = isset($_POST["type"]) && !empty($_POST["type"]) &&
        isset($_POST["name"]) && !empty($_POST["name"]) &&
        isset($_POST["price"]) && !empty($_POST["price"] && $_POST["price"] > 0);
        */
        if ($isDataValid) {
            if (User::isLoggedIn() && User::isAdmin()) {
                if(self::containsRoom($_SESSION["user"]["hid"], $_POST["rid"])) {
                    RoomDB::updateRoom($data["rid"], $data["name"], $data["typeOfRoom"], $data["price"]);
                    ViewHelper::redirect(BASE_URL . "/hotel/room?rid=" . $data["rid"]);
                }
                else {
                    ViewHelper::redirect(BASE_URL . "hotel");
                }
        } else {
               ViewHelper::redirect(BASE_URL . "hotel");
            }
        }
        else {
            self::showEditForm($errors, $data);
        }
    }

    public static function showEditForm($errors = [], $data = []) {
        //ViewHelper::render("view/access-denied.php");
        if (User::isLoggedIn() && User::isAdmin()) {
            if (isset($_GET["rid"]) && !empty($_GET["rid"])) {
              $cond = self::containsRoom($_SESSION["user"]["hid"], $_GET["rid"]);
            }
            else {
                $cond = self::containsRoom($_SESSION["user"]["hid"], $_POST["rid"]);
            }
            if (!$cond) {
                ViewHelper::redirect(BASE_URL . "hotel");
                return;
            }
            if (empty($data)) {
                if (isset($_GET["rid"]) && !empty($_GET["rid"])) {
                    $data = RoomDB::getRoom($_GET["rid"]);
                }
                else {
                    $data = RoomDB::getRoom($_POST["rid"]);
                }
            }

            if (empty($errors)) {
                foreach($data as $key => $value) {
                    $errors[$key] = "";
                }
            }
            ViewHelper::render("view/room-edit.php", ["room" => $data, "errors" => $errors]);
        }
        else {
            ViewHelper::redirect(BASE_URL . "room");
        }
    }

    public static function deleteRoom() {
        $validDelete = isset($_POST["delete_confirmation"]) && isset($_POST["rid"]) && !empty($_POST["rid"]);
        if ($validDelete) {
            if (User::isLoggedIn() && User::isAdmin() &&
            self::containsRoom($_SESSION["user"]["hid"], $_POST["rid"])) {

                if(RoomDB::deleteRoom($_POST["rid"])) {

                    $url = BASE_URL . "hotel";
                }
                else {
                   self::showEditForm(["error" => "You cannot delete the room it is already reserved"], RoomDB::getRoom($_POST["rid"]));
                   return;
                }
            }
            else {
                $url = "hotel";
            }
        } else {
            if (isset($_POST["rid"])) {
                $url =  BASE_URL . "hotel/room/edit?id=" . $_POST["rid"];
            } else {
                $url =  BASE_URL . "hotel";
            }
        }
        ViewHelper::redirect($url);
    }

    public static function searchRooms() {
        ViewHelper::render("view/search-hotel-rooms.php");
    }

    public static function searchApi() {
        if (!User::isLoggedIn() || !User::isAdmin()) {
            return;
        }
        if (isset($_GET["query"]) && !empty($_GET["query"])) {
            $hits = HotelDB::search(User::getHid(), $_GET["query"]);
        } else {
            $hits = [];
        }

        header('Content-type: application/json; charset=utf-8');
        echo json_encode($hits);
    }
}