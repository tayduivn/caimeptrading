<?php
require "lib/google-api-php-client-2.2.2/vendor/autoload.php";

Class ordertireController Extends baseController {
    
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        /*if ($_SESSION['role_logined'] == 4) {
            return $this->view->redirect('admin');
        }*/
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_order_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $import_tire_order_model = $this->model->get('importtireorderModel');
        $import_tire_list_model = $this->model->get('importtirelistModel');
        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');

        $tonkho = array();
        
        $tire_buys = $tire_buy_model->getAllTire();
        foreach ($tire_buys as $buy) {
            $tonkho['brand'][$buy->tire_buy_brand] = isset($tonkho['brand'][$buy->tire_buy_brand])?$tonkho['brand'][$buy->tire_buy_brand]+$buy->tire_buy_volume:$buy->tire_buy_volume;
            $tonkho['size'][$buy->tire_buy_size] = isset($tonkho['size'][$buy->tire_buy_size])?$tonkho['size'][$buy->tire_buy_size]+$buy->tire_buy_volume:$buy->tire_buy_volume;
            $tonkho['pattern'][$buy->tire_buy_pattern] = isset($tonkho['pattern'][$buy->tire_buy_pattern])?$tonkho['pattern'][$buy->tire_buy_pattern]+$buy->tire_buy_volume:$buy->tire_buy_volume;
        }

        $tire_sales = $tire_sale_model->getAllTire();
        foreach ($tire_sales as $sale) {
            $tonkho['brand'][$sale->tire_brand] = isset($tonkho['brand'][$sale->tire_brand])?$tonkho['brand'][$sale->tire_brand]-$sale->volume:(0-$sale->volume);
            $tonkho['size'][$sale->tire_size] = isset($tonkho['size'][$sale->tire_size])?$tonkho['size'][$sale->tire_size]-$sale->volume:(0-$sale->volume);
            $tonkho['pattern'][$sale->tire_pattern] = isset($tonkho['pattern'][$sale->tire_pattern])?$tonkho['pattern'][$sale->tire_pattern]-$sale->volume:(0-$sale->volume);
        }

     
        $tire_goings = $import_tire_order_model->getAllImport(array('where'=>'import_tire_order_total > 0 AND (import_tire_order_status=2 OR import_tire_order_status=4)','order_by'=>'import_tire_order_expect_date ASC'));
        foreach ($tire_goings as $tire_going) {
            $goings = $import_tire_list_model->getAllImport(array('where'=>'import_tire_order='.$tire_going->import_tire_order_id)); //tire_brand thay tire_brand_group
            foreach ($goings as $going) {
                $tonkho['brand'][$going->tire_brand] = isset($tonkho['brand'][$going->tire_brand])?$tonkho['brand'][$going->tire_brand]+$going->tire_number:$going->tire_number;
	            $tonkho['size'][$going->tire_size] = isset($tonkho['size'][$going->tire_size])?$tonkho['size'][$going->tire_size]+$going->tire_number:$going->tire_number;
	            $tonkho['pattern'][$going->tire_pattern] = isset($tonkho['pattern'][$going->tire_pattern])?$tonkho['pattern'][$going->tire_pattern]+$going->tire_number:$going->tire_number;
            }
        }

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name ASC'));

        foreach ($tire_brands as $key => $brand) {
        	if (!isset($tonkho['brand'][$brand->tire_brand_id]) || $tonkho['brand'][$brand->tire_brand_id] == 0) {
        		unset($tire_brands[$key]);
        	}
        }
        foreach ($tire_sizes as $key => $size) {
        	if (!isset($tonkho['size'][$size->tire_size_id]) || $tonkho['size'][$size->tire_size_id] == 0) {
        		unset($tire_sizes[$key]);
        	}
        }
        foreach ($tire_patterns as $key => $pattern) {
        	if (!isset($tonkho['pattern'][$pattern->tire_pattern_id]) || $tonkho['pattern'][$pattern->tire_pattern_id] == 0) {
        		unset($tire_patterns[$key]);
        	}
        }

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;

        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff(array('where'=>'status=1','order_by'=>'staff_name ASC'));
        $this->view->data['staffs'] = $staffs;


        $this->view->show('ordertire/index');
    }
    public function printhd() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $order = $this->registry->router->param_id;
        $ngay = $this->registry->router->order;
        $thang = $this->registry->router->order_by;
        $nam = $this->registry->router->page;
        $sohd = $this->registry->router->addition;

        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'order_tire, tire_pattern, tire_brand, tire_size','where'=> 'order_tire=order_tire_id AND tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_lists = $tire_order_list_model->getAllTire($data,$join);

        $trugiam = $orders->discount+$orders->reduce;
        if ($trugiam<0) {
            $trugiam = 0;
        }
        $giam = $trugiam/$orders->order_tire_number;
        $giam = $giam/1.1;

        $items = array();
        $congtien=0;
        $tongcong=0;
        $i=1;
        foreach ($order_lists as $order_list) {
            $items['stt'][] = $i++;
            $items['ten'][] = $order_list->tire_brand_name.' '.$order_list->tire_size_number.' '.$order_list->tire_pattern_name;
            if (strpos($order_list->tire_size_number, '22.5') !== false) {
                $items['dvt'][] = 'Cái';
            }
            else{
                $items['dvt'][] = 'Bộ';
            }
            $items['sl'][] = $order_list->tire_number;
            $dg = $order_list->check_price_vat==1?$order_list->tire_price_vat*$order_list->vat_percent*0.1/1.1:$order_list->tire_price*$order_list->vat_percent*0.1;
            $dg = $dg-$giam;
            $items['dg'][] = round($dg);
            $items['tt'][] = round($dg*$order_list->tire_number);

            $congtien += $dg*$order_list->tire_number;
            //$tongcong += $order_list->tire_price_vat*$order_list->tire_number;
        }

        $tienthue = round($congtien*0.1);
        $tongcong = round($congtien+$tienthue);
        $congtien = round($congtien);

        $ngay = ($ngay=="" || $ngay==NULL)?date('d',$orders->delivery_date):$ngay;
        $thang = ($thang=="" || $nam==NULL)?date('m',$orders->delivery_date):$thang;
        $nam = ($nam=="" || $nam==NULL)?date('Y',$orders->delivery_date):$nam;
        
        
        $this->view->data['items'] = $items;
        $this->view->data['customers'] = $customers;

        $this->view->data['ngay'] = $ngay;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        $this->view->data['sohd'] = $sohd;
        $this->view->data['congtien'] = $congtien;
        $this->view->data['tienthue'] = $tienthue;
        $this->view->data['tongcong'] = $tongcong;

        $this->view->show('ordertire/printhd');
    }
    public function printpage() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        $ngay = $this->registry->router->order;
        $thang = $this->registry->router->order_by;
        $nam = substr($this->registry->router->page,2);
        $sohd = $this->registry->router->addition;

        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'order_tire, tire_pattern, tire_brand, tire_size','where'=> 'order_tire=order_tire_id AND tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_lists = $tire_order_list_model->getAllTire($data,$join);

        $trugiam = $orders->discount+$orders->reduce;
        if ($trugiam<0) {
            $trugiam = 0;
        }
        $giam = $trugiam/$orders->order_tire_number;
        $giam = $giam/1.1;

        $items = array();
        $congtien=0;
        $tongcong=0;
        $i=1;
        foreach ($order_lists as $order_list) {
            $items['stt'][] = $i++;
            $items['ten'][] = $order_list->tire_brand_name.' '.$order_list->tire_size_number.' '.$order_list->tire_pattern_name;
            if (strpos($order_list->tire_size_number, '22.5') !== false) {
                $items['dvt'][] = 'Cái';
            }
            else{
                $items['dvt'][] = 'Bộ';
            }
            $items['sl'][] = $order_list->tire_number;
            $dg = $order_list->check_price_vat==1?$order_list->tire_price_vat*$order_list->vat_percent*0.1/1.1:$order_list->tire_price*$order_list->vat_percent*0.1;
            $dg = $dg-$giam;
            $items['dg'][] = round($dg);
            $items['tt'][] = round($dg*$order_list->tire_number);

            $congtien += $dg*$order_list->tire_number;
            //$tongcong += $order_list->tire_price_vat*$order_list->tire_number;
        }

        $tienthue = round($congtien*0.1);
        $tongcong = round($congtien+$tienthue);
        $congtien = round($congtien);

        $ngay = ($ngay=="" || $ngay==NULL)?date('d',$orders->delivery_date):$ngay;
        $thang = ($thang=="" || $nam==NULL)?date('m',$orders->delivery_date):$thang;
        $nam = ($nam=="" || $nam==NULL)?substr(date('Y',$orders->delivery_date),2):$nam;
        
        
        $this->view->data['items'] = $items;
        $this->view->data['customers'] = $customers;

        $this->view->data['ngay'] = $ngay;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        $this->view->data['sohd'] = $sohd;
        $this->view->data['congtien'] = $congtien;
        $this->view->data['tienthue'] = $tienthue;
        $this->view->data['tongcong'] = $tongcong;

        $this->view->show('ordertire/printpage');
    }
    public function printview() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;

        include('lib/phpqrcode/qrlib.php'); 

        $tempDir = "public/images/qr/"; 
        array_map('unlink', glob($tempDir."*"));
        $codeContents = 'https://www.viet-trade.org/onlineservices/tireorder/00'.$order.'1';
        $fileName = 'qr_'.md5($codeContents).'.png'; 
        $pngAbsoluteFilePath = $tempDir.$fileName; 
        QRcode::png($codeContents, $pngAbsoluteFilePath); 
        $this->view->data['img'] = $pngAbsoluteFilePath;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');
        $staff_model = $this->model->get('staffModel');

        $orders = $tire_order_model->getTire($order);

        $staffs = $staff_model->getStaffByWhere(array('account'=>$orders->sale));

        if($orders->delivery_date>0){
            $ngay = date('d',$orders->delivery_date);
            $thang = date('m',$orders->delivery_date);
            $nam = date('Y',$orders->delivery_date);
        }
        else{
            $ngay = date('d');
            $thang = date('m');
            $nam = date('Y');
        }
        
        $order_number = $orders->order_number!=""?$orders->order_number:"lx-".substr($nam, -2).$thang."...";

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        $this->view->data['orders'] = $orders;
        $this->view->data['order_types'] = $order_types;
        $this->view->data['ngay'] = $ngay;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        $this->view->data['order_number'] = $order_number;
        $this->view->data['staffs'] = $staffs;
        $this->view->data['customers'] = $customers;

        $this->view->show('ordertire/printview');
    }
    public function printview2() {
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;

        include('lib/phpqrcode/qrlib.php'); 

        $tempDir = "public/images/qr/"; 
        array_map('unlink', glob($tempDir."*"));
        $codeContents = 'https://www.viet-trade.org/onlineservices/tireorder/00'.$order.'1';
        $fileName = 'qr_'.md5($codeContents).'.png'; 
        $pngAbsoluteFilePath = $tempDir.$fileName; 
        QRcode::png($codeContents, $pngAbsoluteFilePath); 
        $this->view->data['img'] = $pngAbsoluteFilePath;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');
        $staff_model = $this->model->get('staffModel');

        $orders = $tire_order_model->getTire($order);

        $staffs = $staff_model->getStaffByWhere(array('account'=>$orders->sale));

        if($orders->delivery_date>0){
            $ngay = date('d',$orders->delivery_date);
            $thang = date('m',$orders->delivery_date);
            $nam = date('Y',$orders->delivery_date);
        }
        else{
            $ngay = date('d');
            $thang = date('m');
            $nam = date('Y');
        }
        
        $order_number = $orders->order_number!=""?$orders->order_number:"lx-".substr($nam, -2).$thang."...";

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        $this->view->data['orders'] = $orders;
        $this->view->data['order_types'] = $order_types;
        $this->view->data['ngay'] = $ngay;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;
        $this->view->data['order_number'] = $order_number;
        $this->view->data['staffs'] = $staffs;
        $this->view->data['customers'] = $customers;

        $this->view->show('ordertire/printview2');
    }
    public function getPattern(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $brand = trim($_POST['data']);
            $str = "";

            $tire_buy_model = $this->model->get('tirebuyModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_going_model = $this->model->get('tiregoingModel');

            $tire_buys = $tire_buy_model->queryTire('SELECT tire_buy_pattern FROM tire_buy WHERE tire_buy_brand = '.$brand.' GROUP BY tire_buy_pattern');
            foreach ($tire_buys as $tire_buy) {
                $tire_patterns = $tire_pattern_model->getTire($tire_buy->tire_buy_pattern);
                $str .= '<option value="'.$tire_patterns->tire_pattern_id.'">'.$tire_patterns->tire_pattern_name.'</option>';
            }
            $tire_goings = $tire_going_model->queryTire('SELECT tire_pattern FROM tire_going WHERE tire_pattern NOT IN (SELECT tire_buy_pattern FROM tire_buy WHERE tire_buy_brand = '.$brand.') AND tire_brand = '.$brand.' GROUP BY tire_pattern');
            foreach ($tire_goings as $tire_going) {
                $tire_patterns = $tire_pattern_model->getTire($tire_going->tire_pattern);
                $str .= '<option value="'.$tire_patterns->tire_pattern_id.'">'.$tire_patterns->tire_pattern_name.'</option>';
            }

            echo $str;
        }
    }
    public function waiting($id) {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        if (!$id) {
            return $this->view->redirect('ordertirewaiting');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_order_id';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 50;
        }

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire(array('order_by'=>'tire_brand_name ASC'));
        $tire_sizes = $tire_size_model->getAllTire(array('order_by'=>'tire_size_number ASC'));
        $tire_patterns = $tire_pattern_model->getAllTire(array('order_by'=>'tire_pattern_name ASC'));

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;

        $order_tire_waiting_model = $this->model->get('ordertirewaitingModel');
        $tire_desired_model = $this->model->get('tiredesiredModel');
        $customer_model = $this->model->get('customerModel');

        $order_tires = $order_tire_waiting_model->getTire($id);
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand_code = tire_brand_id AND tire_size = tire_size_id AND tire_pattern_code = tire_pattern_id');
        $tire_desireds = $tire_desired_model->getAllTire(array('where'=>'order_tire_waiting = '.$id),$join);

        $arr_max = array();

        $tire_buy_model = $this->model->get('tirebuyModel');
        $tire_sale_model = $this->model->get('tiresaleModel');
        $tire_going_model = $this->model->get('tiregoingModel');

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $customers = $customer_model->getCustomer($order_tires->customer);

        foreach ($tire_desireds as $tire) {
            $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$tire->tire_brand_code.' AND tire_buy_size = '.$tire->tire_size.' AND tire_buy_pattern = '.$tire->tire_pattern_code));
            foreach ($tire_buys as $tire_buy) {
                $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]+$tire_buy->tire_buy_volume:$tire_buy->tire_buy_volume;
            }

            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$tire->tire_brand_code.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern_code));
            foreach ($tire_sales as $tire_sale) {
                $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]-$tire_sale->volume:0;
            }

            $tire_goings = $tire_going_model->getAllTire(array('where'=>'(status IS NULL OR status != 1 ) AND tire_brand = '.$tire->tire_brand_code.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern_code));
            foreach ($tire_goings as $tire_going) {
                $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]+$tire_going->tire_number:$tire_going->tire_number;
            }

            $order_tire = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
            foreach ($order_tire as $order) {
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id.' AND tire_brand = '.$tire->tire_brand_code.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern_code));
                foreach ($order_tire_lists as $list) {
                    $arr_max[$tire->tire_desired_id] = isset($arr_max[$tire->tire_desired_id])?$arr_max[$tire->tire_desired_id]-$list->tire_number:0;
                }
            }
        }

        $this->view->data['arr_max'] = $arr_max;
        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['customers'] = $customers;
        $this->view->data['tire_desireds'] = $tire_desireds;

        $this->view->data['order_tire_waiting'] = $id;

        $this->view->show('ordertire/waiting');
    }
    public function photo() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Hình ảnh đơn hàng';

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tires = $order_tire_model->getAllTire(array('where'=>'order_tire_status=1','order_by'=>'order_number','order'=>'ASC'));
        $this->view->data['order_tires'] = $order_tires;

        $this->view->show('ordertire/photo');
    }
    public function uploadphoto() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_FILES['myFile'])) {
                $order_tire = $_POST['order_tire'];
                $photo_model = $this->model->get('ordertirephotoModel');
                $output_dir = "public/images/upload/";

                $error =$_FILES["myFile"]["error"];
                //You need to handle  both cases
                //If Any browser does not support serializing of multiple files using FormData() 
                if(!is_array($_FILES["myFile"]["name"])) //single file
                {
                    $fileName = $_FILES["myFile"]["name"];
                    $fullpath = $output_dir.$fileName;
                    $file_info = pathinfo($fullpath);
                    $uploaded_filename = $file_info['filename'];

                    $count = 1;                 
                    while (file_exists($fullpath)) {
                      $info = pathinfo($fullpath);
                      $fullpath = $info['dirname'] . '/' . $uploaded_filename
                      . '(' . $count++ . ')'
                      . '.' . $info['extension'];
                    }
                    move_uploaded_file($_FILES["myFile"]["tmp_name"],$fullpath);

                    $data = array(
                        'photo_url'=>$fullpath,
                        'create_user'=>$_SESSION['userid_logined'],
                        'order_tire_photo_date'=>strtotime(date('d-m-Y')),
                        'order_tire' => $order_tire,
                    );
                    $photo_model->createTire($data);

                    echo 'Successful';
                }
                else  //Multiple files, file[]
                {
                  $fileCount = count($_FILES["myFile"]["name"]);
                  for($i=0; $i < $fileCount; $i++)
                  {
                    $fileName = $_FILES["myFile"]["name"][$i];
                    $fullpath = $output_dir.$fileName;
                    $file_info = pathinfo($fullpath);
                    $uploaded_filename = $file_info['filename'];

                    $count = 1;                 
                    while (file_exists($fullpath)) {
                      $info = pathinfo($fullpath);
                      $fullpath = $info['dirname'] . '/' . $uploaded_filename
                      . '(' . $count++ . ')'
                      . '.' . $info['extension'];
                    }
                    move_uploaded_file($_FILES["myFile"]["tmp_name"][$i],$fullpath);
                    
                    $data = array(
                        'photo_url'=>$fullpath,
                        'create_user'=>$_SESSION['userid_logined'],
                        'order_tire_photo_date'=>strtotime(date('d-m-Y')),
                        'order_tire' => $order_tire,
                    );
                    $photo_model->createTire($data);

                    echo 'Successful';
                  }
                
                }
            }
        }
    }
    public function orderlist() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn đặt hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $thang = isset($_POST['tha']) ? $_POST['tha'] : null;
            $nam = isset($_POST['na']) ? $_POST['na'] : null;
            $code = isset($_POST['tu']) ? $_POST['tu'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_status ASC, ABS(SUBSTRING(order_number,4,4)) DESC, ABS(SUBSTRING(order_number,4))';
            $order = $this->registry->router->order_by ? $this->registry->router->order : 'DESC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = "";
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $sodonhang = $this->registry->router->addition;

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array(
            'order_by'=> 'customer_name',
            'order'=> 'ASC',
            ));

        $this->view->data['customers'] = $customers;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
        );
        if ($nv == 1) {
            $data = array(
                'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
            );
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "") {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] = 'order_tire_id = '.$code;
        }

        if ($sodonhang != "") {
            $data['where'] = 'order_number = "'.$sodonhang.'"';
        }
        
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = order_tire.customer AND user_id = sale');
        
        $tongsodong = count($order_tire_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
            );

        if ($nv == 1) {
            $data['where'] = 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc);
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "") {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] = 'order_tire_id = '.$code;
        }

        if ($sodonhang != "") {
            $data['where'] = 'order_number = "'.$sodonhang.'"';
        }

        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }*/

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"  
                OR username LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tires = $order_tire_model->getAllTire($data,$join);
        
        $staff_model = $this->model->get('staffModel');
        $staffs = $staff_model->getAllStaff();
        $staff = array();
        foreach ($staffs as $st) {
            $staff[$st->staff_id] = $st->staff_name;
        }

        $staff_sales = $staff_model->getAllStaff(array('where'=>'status=1','order_by'=>'staff_name ASC'));
        $this->view->data['staffs'] = $staff_sales;

        $receivable_model = $this->model->get('receivableModel');     
        $order_pay = array();   

        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');
        $tire_price_discount_number_model = $this->model->get('tirepricediscountnumberModel');
        $tire_list_model = $this->model->get('ordertirelistModel');
        $lift_model = $this->model->get('liftModel');
        $lift = array();

        $tiresale_model = $this->model->get('tiresaleModel');
        $check_salary_percent_model = $this->model->get('checksalarypercentModel');

        $qr = 'SELECT * FROM check_salary_percent WHERE create_time <= '.strtotime($ketthuc).' ORDER BY create_time DESC LIMIT 1';
        $check_salarys = $check_salary_percent_model->querySalary($qr);
        $arr_salary = array();
        foreach ($check_salarys as $check_salary) {
            $arr_salary['sanluong'] = $check_salary->order_number;
            $arr_salary['moi'] = $check_salary->order_new;
            $arr_salary['cu'] = $check_salary->order_old;
            $arr_salary['phantram'] = $check_salary->order_percent;
        }

        $order_tire_discount = array();
        $this_month = array();
        $last_month = array();
        $bonus_salary = array();

        $group_order = array();

        foreach ($order_tires as $tire) {
            $receivables = $receivable_model->getCostsByWhere(array('order_tire'=>$tire->order_tire_id));
            $order_pay[$tire->order_tire_id] = $receivables->pay_money;

            $sum_order_month = $order_tire_model->queryTire('SELECT COUNT(order_tire_id) AS tong FROM order_tire WHERE customer='.$tire->customer.' AND (order_tire_status IS NULL OR order_tire_status!=1) GROUP BY customer');
            if ($tire->order_tire_status!=1) {
                $total_order_month = 0;
                foreach ($sum_order_month as $sum) {
                    $total_order_month = $sum->tong;
                }

                if ($total_order_month>1) {
                    $group_order[$tire->customer]['order'][] = $tire;
                    $group_order[$tire->customer]['customer'] = $tire->customer_name;
                    $group_order[$tire->customer]['sl'] = isset($group_order[$tire->customer]['sl'])?$group_order[$tire->customer]['sl']+$tire->order_tire_number:$tire->order_tire_number;
                    $group_order[$tire->customer]['vat'] = isset($group_order[$tire->customer]['vat'])?$group_order[$tire->customer]['vat']+$tire->vat:$tire->vat;
                    $group_order[$tire->customer]['discount'] = isset($group_order[$tire->customer]['discount'])?$group_order[$tire->customer]['discount']+$tire->discount+$tire->reduce:$tire->discount+$tire->reduce;
                    $group_order[$tire->customer]['warranty'] = isset($group_order[$tire->customer]['warranty'])?$group_order[$tire->customer]['warranty']+$tire->warranty:$tire->warranty;
                    $group_order[$tire->customer]['total'] = isset($group_order[$tire->customer]['total'])?$group_order[$tire->customer]['total']+$tire->total:$tire->total;
                }
            }

            $lifts = $lift_model->getLiftByWhere(array('order_tire'=>$tire->order_tire_id));
            if ($lifts) {
                $sts = explode(',', $lifts->staff);
                foreach ($sts as $key) {
                    if (!isset($lift[$tire->order_tire_id])) {
                        $lift[$tire->order_tire_id] = $staff[$key];
                    }
                    else{
                        $lift[$tire->order_tire_id] .= ','.$staff[$key];
                    }
                }
            }  

            $ngay = $tire->delivery_date>0?$tire->delivery_date:strtotime(date('d-m-Y'));

            $total_order_before = 0; //Tổng sản lượng tháng trước
            $total_order = 0; //Tổng sản lượng tháng này

            $myDate = strtotime(date("d-m-Y", $ngay) . "-1 month" ) ;

            $sum_order = $tiresale_model->queryTire('SELECT SUM(volume) AS tong FROM tire_sale WHERE customer='.$tire->customer.' AND tire_sale_date >= '.strtotime('01-'.date('m-Y',$myDate)).' AND tire_sale_date <= '.strtotime(date('t-m-Y',$myDate)).' GROUP BY customer');
                
            foreach ($sum_order as $sum) {
                $total_order_before = $sum->tong;
            }

            ////////

            $sum_order = $tiresale_model->queryTire('SELECT SUM(volume) AS tong FROM tire_sale WHERE customer='.$tire->customer.' AND tire_sale_date >= '.strtotime('01-'.date('m-Y',$ngay)).' AND tire_sale_date <= '.strtotime(date('t-m-Y',$ngay)).' GROUP BY customer');
            foreach ($sum_order as $sum) {
                $total_order = $sum->tong;
            }
            if ($total_order == 0) {
                $total_order = $tire->order_tire_number;
            }

            $last_month[$tire->order_tire_id] = $total_order_before;
            $this_month[$tire->order_tire_id] = $total_order;

            

            $tongchiphi = $tire->order_cost+$tire->discount+$tire->reduce;
            $tongsoluong = $tire->order_tire_number;      
            
            $data = array(
                'where' => 'tire_number>0 AND order_tire = '.$tire->order_tire_id,
            );
            $sales = $tire_list_model->getAllTire($data);
            foreach ($sales as $sale) {
                if ($sale->tire_price_vat=="" || $sale->tire_price_vat==0) {
                    $dongia = $sale->tire_price+($sale->tire_price*$tire->vat_percent/100);
                }
                else{
                    if ($tire->check_price_vat==1) {
                        $dongia = $sale->tire_price_vat;
                    }
                    else{
                        $dongia = $sale->tire_price+($sale->tire_price*$tire->vat_percent/100);
                    }
                }
                $tire_price_origin = $dongia;

                $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' - 1 days')));
                $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));

                
                $data_q = array(
                    'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );
                $tire_price_discounts = $tire_price_discount_model->getAllTire($data_q);

                $data_n = array(
                    'where' => 'type ='.$tire->customer_type.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date=0 OR end_date > '.$ngaytruoc.')',
                    'order_by' => 'number ASC, start_date',
                    'order' => 'DESC',
                );
                $tire_price_discount_numbers = $tire_price_discount_number_model->getAllTire($data_n);

                $data_e = array(
                    'where' => 'tire_brand ='.$sale->tire_brand.' AND tire_size ='.$sale->tire_size.' AND tire_pattern ='.$sale->tire_pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                    'order_by' => 'start_date',
                    'order' => 'DESC',
                    'limit' => 1,
                );

                $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);

                $tire_prices = $dongia;
                $tire_price_agents = $dongia;
                $giacongkhai = $dongia;

                foreach ($tire_price_discounts as $price) {
                    $percent_number = 1;
                    foreach ($tire_price_discount_numbers as $discount_number) {
                        if ($total_order >= $discount_number->number) {
                            $percent_number = (100-$discount_number->percent)/100;
                        }
                    }

                    $tire_prices = (round($price->tire_price*$percent_number/1000)*1000);
                    $tire_price_agents = (round($price->tire_price*$percent_number/1000)*1000);
                    $tire_price_origin = ($price->tire_price*0.75); // giá công khai giảm 25% + vc
                    $giacongkhai = $price->tire_price;

                    foreach ($tire_price_discount_events as $event) {
                        if ($event->percent_discount > 0) {
                            $tire_prices = (round($price->tire_price*$percent_number/1000)*1000)*((100-$event->percent_discount)/100);
                            $tire_price_agents = (round($price->tire_price*$percent_number/1000)*1000)*((100-$event->percent_discount)/100);
                            $tire_price_origin = ($price->tire_price*0.75)*((100-$event->percent_discount)/100);
                            $giacongkhai = $price->tire_price*((100-$event->percent_discount)/100);
                        }
                        else{
                            $tire_prices = (round($price->tire_price*$percent_number/1000)*1000)-$event->money_discount;
                            $tire_price_agents = (round($price->tire_price*$percent_number/1000)*1000)-$event->money_discount;
                            $tire_price_origin = ($price->tire_price*0.75)-$event->money_discount;
                            $giacongkhai = $price->tire_price-$event->money_discount;
                        }
                    }
                }

                $chiphi = $tongchiphi/$tongsoluong-77000;
                //$chiphi = $chiphi>0?$chiphi:0;
                
                $gia = $dongia-$chiphi;
                $dongia = $dongia-$chiphi;

                if ($tire->customer_type==1) {
                    if ($tire->vat==0) {
                        if ($tire_price_agents<5000000) {
                            $discount = 160000;
                        }
                        else if($tire_price_agents>=7000000){
                            $discount = 300000;
                        }
                        else{
                            $discount = 200000;
                        }

                        $gia = $gia+$discount;
                        $dongia = $dongia+$discount;
                    }
                }
                else{
                    if ($tire->vat==0) {
                        if ($tire_prices<5000000) {
                            $discount = 160000;
                        }
                        else if($tire_prices>=7000000){
                            $discount = 300000;
                        }
                        else{
                            $discount = 200000;
                        }

                        $gia = $gia+$discount;
                        $dongia = $dongia+$discount;
                    }
                }
                

                $order_tire_discount[$tire->order_tire_id]['thu'] = isset($order_tire_discount[$tire->order_tire_id]['thu'])?$order_tire_discount[$tire->order_tire_id]['thu']+$gia*$sale->tire_number:$gia*$sale->tire_number;
                $order_tire_discount[$tire->order_tire_id]['gia'] = isset($order_tire_discount[$tire->order_tire_id]['gia'])?$order_tire_discount[$tire->order_tire_id]['gia']+$giacongkhai*$sale->tire_number:$giacongkhai*$sale->tire_number;

                $salary = (($gia-$tire_price_origin)*$arr_salary['phantram']/100)*$sale->tire_number;


                // if ($dongia < $tire_prices*0.95 || $dongia < $tire_price_origin) {
                //     $salary = 0;
                // }
                // else{
                //     if ($tire->customer_type==1) {
                //         if ($dongia < $tire_prices*0.96) {
                //             $salary = $arr_salary['sanluong']*$sale->tire_number;
                //         }
                //     }
                //     else{
                //         if ($dongia < $tire_prices*0.98) {
                //             $salary = $arr_salary['sanluong']*$sale->tire_number;
                //         }
                //     }

                //     if ($salary < $arr_salary['sanluong']*$sale->tire_number) {
                //         $salary = $arr_salary['sanluong']*$sale->tire_number;
                //     }
                // }

                if ($dongia < $giacongkhai*0.748) {
                    $salary = 0;
                }
                else{
                    if ($tire->customer_type==1) {
                        $quydinh = $tire_price_agents*100/$giacongkhai;
                    }
                    else{
                        $quydinh = $tire_prices*100/$giacongkhai;
                    }
                    
                    $ban = $dongia*100/$giacongkhai;
                    if ($ban < ($quydinh-4)) {
                        $salary = $arr_salary['sanluong']*$sale->tire_number;
                    }

                    if ($salary < $arr_salary['sanluong']*$sale->tire_number) {
                        $salary = $arr_salary['sanluong']*$sale->tire_number;
                    }
                }

                $bonus_salary[$tire->order_tire_id] = isset($bonus_salary[$tire->order_tire_id])?$bonus_salary[$tire->order_tire_id]+$salary:$salary;
            }
        }

        $this->view->data['salary'] = $bonus_salary;

        $this->view->data['this_month'] = $this_month;

        $this->view->data['last_month'] = $last_month;

        $this->view->data['order_tire_discount'] = $order_tire_discount;

        $this->view->data['lift'] = $lift;

        $this->view->data['order_tires'] = $order_tires;

        $this->view->data['group_order'] = $group_order;

        $this->view->data['order_pay'] = $order_pay;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $this->view->show('ordertire/orderlist');
    }

    public function report() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {

            return $this->view->redirect('user/login');

        }
        
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Đơn đặt hàng';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
            $trangthai = isset($_POST['trangthai']) ? $_POST['trangthai'] : null;
            $nv = isset($_POST['nv']) ? $_POST['nv'] : null;
            $batdau = isset($_POST['batdau']) ? $_POST['batdau'] : null;
            $ketthuc = isset($_POST['ketthuc']) ? $_POST['ketthuc'] : null;
            $thang = isset($_POST['tha']) ? $_POST['tha'] : null;
            $nam = isset($_POST['na']) ? $_POST['na'] : null;
            $code = isset($_POST['tu']) ? $_POST['tu'] : null;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'order_tire_status ASC, ABS(SUBSTRING(order_number,4,4)) DESC, ABS(SUBSTRING(order_number,4))';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 18446744073709;
            $trangthai = 0;
            $nv = "";
            $batdau = '01-'.date('m-Y');
            $ketthuc = date('t-m-Y');
            $thang = (int)date('m',strtotime($batdau));
            $nam = date('Y',strtotime($batdau));
            $code = "";
        }

        $ma = $this->registry->router->param_id;

        $tg = $this->registry->router->page;
        $stf = $this->registry->router->order_by;

        $ngayketthuc = date('d-m-Y', strtotime($ketthuc. ' + 1 days'));

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getAllCustomer(array(
            'order_by'=> 'customer_name',
            'order'=> 'ASC',
            ));

        $this->view->data['customers'] = $customers;

        $vendor_model = $this->model->get('shipmentvendorModel');
        $vendors = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

        $this->view->data['vendor_list'] = $vendors;

        $user_model = $this->model->get('userModel');
        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data['name'][$user->user_id] = $user->username;
            $user_data['id'][$user->user_id] = $user->user_id;
        }
        $this->view->data['users'] = $user_data;

        
        
        $data = array(
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
        );

        if ($nv == 1) {
            $data = array(
                'where' => 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc),
            );
        }

        if (isset($tg) && $tg > 0) {
            $data['where'] = 'delivery_date >= '.$tg.' AND delivery_date <= '.strtotime(date('t-m-Y',$tg));

            $batdau = '01-'.date('m-Y',$tg);
            $ketthuc = date('t-m-Y',$tg);

            if (isset($stf) && $stf > 0) {
                $data['where'] .= ' AND sale = '.$stf;
                $page = 1;
                $order_by = 'order_tire_status ASC, delivery_date';
                $order = ' ASC';
            }
        }

        $thang = (int)date('m',strtotime($batdau));
        $nam = date('Y',strtotime($batdau));

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "" && $ma != 0) {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        $order_tire_model = $this->model->get('ordertireModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $join = array('table'=>'customer, user','where'=>'customer.customer_id = order_tire.customer AND user_id = sale');
        
        $tongsodong = count($order_tire_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;
        $this->view->data['trangthai'] = $trangthai;
        $this->view->data['nv'] = $nv;
        $this->view->data['batdau'] = $batdau;
        $this->view->data['ketthuc'] = $ketthuc;
        $this->view->data['thang'] = $thang;
        $this->view->data['nam'] = $nam;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => ' ( (order_tire_status IS NULL OR order_tire_status = 0) OR (order_tire_status = 1 AND delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc).') )',
            );

        if ($nv == 1) {
            $data['where'] = 'delivery_date >= '.strtotime($batdau).' AND delivery_date < '.strtotime($ngayketthuc);
        }

        if (isset($tg) && $tg > 0) {
            $data['where'] = 'delivery_date >= '.$tg.' AND delivery_date <= '.strtotime(date('t-m-Y',$tg));

            if (isset($stf) && $stf > 0) {
                $data['where'] .= ' AND sale = '.$stf;
            }
        }

        if ($trangthai > 0) {
            $data['where'] .= ' AND customer = '.$trangthai;
        }
        if ($nv != "") {
            $data['where'] .= ' AND order_tire_status = '.$nv;
        }

        if ($ma != "" && $ma != 0) {
            $code = '"'.$ma.'"';
        }

        if ($code != "" && $code != "undefined") {
            $data['where'] .= ' AND order_number = '.$code;
        }

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 2 && $_SESSION['role_logined'] != 8) {
            $data['where'] = $data['where'].' AND sale = '.$_SESSION['userid_logined'];
        }

        if ($keyword != '') {
            $search = '( order_number LIKE "%'.$keyword.'%" 
                OR customer_name LIKE "%'.$keyword.'%"   )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        $order_tire_list_model = $this->model->get('ordertirelistModel');

        $order_tires = $order_tire_model->getAllTire($data,$join);

        $tire_import_model = $this->model->get('tireimportModel');
        $invoice_tire_model = $this->model->get('invoicetireModel');
        
        $invoice_data = array();
        $costs = array();
        foreach ($order_tires as $tire) {
            $ngay = $tire->order_tire_status==1?$tire->delivery_date:strtotime(date('d-m-Y'));
            $ngayketthuc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));
            $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$tire->order_tire_id));
            foreach ($order_tire_lists as $l) {
                $gia = 0;
                $data = array(
                    'where' => '(order_num = "" OR order_num IS NULL) AND start_date < '.$ngayketthuc.' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                    'order_by' => 'start_date',
                    'order' => 'DESC, tire_import_id DESC',
                    'limit' => 1,
                );
                $tire_imports = $tire_import_model->getAllTire($data);
                foreach ($tire_imports as $tire_import) {
                    $gia = $tire_import->tire_price;
                }
                
                if ($tire->order_number != "") {
                    $data = array(
                        'where' => 'order_num = "'.$tire->order_number.'" AND start_date <= '.strtotime(date('t-m-Y',$ngay)).' AND tire_brand = '.$l->tire_brand.' AND tire_size = '.$l->tire_size.' AND tire_pattern = '.$l->tire_pattern,
                        'order_by' => 'start_date',
                        'order' => 'DESC, tire_import_id DESC',
                        'limit' => 1,
                    );
                    $tire_imports = $tire_import_model->getAllTire($data);
                    foreach ($tire_imports as $tire_import) {
                        $gia = $tire_import->tire_price;
                    }
                }

                $costs[$tire->order_tire_id] = isset($costs[$tire->order_tire_id])?$costs[$tire->order_tire_id]+$l->tire_number*$gia:$l->tire_number*$gia;
            }

            $invoice = $invoice_tire_model->getAllInvoice(array('where'=>'order_tire='.$tire->order_tire_id));
            foreach ($invoice as $invoices) {
                $invoice_data[$tire->order_tire_id]['number'] = isset($invoice_data[$tire->order_tire_id]['number'])?$invoice_data[$tire->order_tire_id]['number'].' | '.$invoices->invoice_tire_number:$invoices->invoice_tire_number;
                $invoice_data[$tire->order_tire_id]['date'] = isset($invoice_data[$tire->order_tire_id]['date'])?$invoice_data[$tire->order_tire_id]['date'].' | '.$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date):$this->lib->hien_thi_ngay_thang($invoices->invoice_tire_date);
            }
        }
        
        $this->view->data['costs'] = $costs;
        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['invoice_data'] = $invoice_data;

        $this->view->data['lastID'] = isset($order_tire_model->getLastTire()->order_tire_id)?$order_tire_model->getLastTire()->order_tire_id:0;

        $tire_sale_model = $this->model->get('tiresaleModel');
        $join = array('table'=>'customer, user, staff','where'=>'customer.customer_id = tire_sale.customer AND staff_id = sale AND account = user_id');
        $data = array(
            'where' => 'customer = 169 AND tire_sale_date >= '.strtotime($batdau).' AND tire_sale_date < '.strtotime($ngayketthuc),
        );
        $sales = $tire_sale_model->getAllTire($data,$join);

        $costs2 = array();
        foreach ($sales as $tire) {
            $gia = 0;
            $data = array(
                'where' => '(order_num = "" OR order_num IS NULL) AND start_date <= '.strtotime(date('t-m-Y',$tire->tire_sale_date)).' AND tire_brand = '.$tire->tire_brand.' AND tire_size = '.$tire->tire_size.' AND tire_pattern = '.$tire->tire_pattern,
                'order_by' => 'start_date',
                'order' => 'DESC, tire_import_id DESC',
                'limit' => 1,
            );
            $tire_imports = $tire_import_model->getAllTire($data);
            foreach ($tire_imports as $tire_import) {
                $gia = $tire_import->tire_price;
            }

            $costs2[$tire->tire_sale_id] = isset($costs2[$tire->tire_sale_id])?$costs2[$tire->tire_sale_id]+$tire->volume*$gia:$tire->volume*$gia;
        }
        $this->view->data['costs2'] = $costs2;
        $this->view->data['sales'] = $sales;

        $this->view->show('ordertire/report');
    }

    public function getcustomer(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customerModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            
                if ($_POST['keyword'] == "*") {
                    $list = $customer_model->getAllCustomer();
                }
                else{
                    $data = array(
                    'where'=>'( customer_name LIKE "%'.$_POST['keyword'].'%" )',
                    );
                    $list = $customer_model->getAllCustomer($data);
                }
                
                $expect_date = "";

                foreach ($list as $rs) {
                    // put in bold the written text
                    $customer_name = $rs->customer_name;
                    if ($_POST['keyword'] != "*") {
                        $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_name);
                    }

                    if ($rs->customer_expect_date != null) {
                        $expect_date = date('d-m-Y',strtotime($rs->customer_expect_date.'-'.date('m-Y',strtotime(date('d-m-Y')))));
                    }
                    else if ($rs->customer_after_date != null) {
                        $expect_date = date('d-m-Y',strtotime('+'.$rs->customer_after_date.' day', strtotime(date('d-m-Y'))));
                    }
                    
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$rs->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }
                    // add new option
                    echo '<li onclick="set_item(\''.$rs->customer_name.'\',\''.$rs->customer_id.'\',\''.str_replace("'", "\'", str_replace("\'", "'", $rs->company_name)).'\',\''.$rs->customer_phone.'\',\''.str_replace("'", "\'", str_replace("\'", "'", $rs->customer_address)).'\',\''.$rs->customer_email.'\',\''.$rs->customer_contact.'\',\''.$rs->customer_create_user.'\',\''.$expect_date.'\',\''.$rs->mst.'\',\''.$type.'\')">'.str_replace("'", "\'", str_replace("\'", "'", $customer_name)).'</li>';
                
            }
        }
    }
    public function getcustomerinfo(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_sale_model = $this->model->get('tiresaleModel');
            $customer_model = $this->model->get('customerModel');
            $company = trim($_POST['company']);
            $mst = trim($_POST['mst']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);
            $type = trim($_POST['type']);
            $staff = trim($_POST['staff']);
            $customer_name = trim($_POST['customer_name']);
            $customer = trim($_POST['customer']);
            $address = trim($_POST['address']);
            $contact = trim($_POST['contact']);

            $arr = array(
                'company'=>$company,
                'mst'=>$mst,
                'phone'=>$phone,
                'email'=>$email,
                'type'=>$type,
                'staff'=>$staff,
                'customer_name'=>$customer_name,
                'customer'=>$customer,
                'address'=>$address,
                'contact'=>$contact,
            );

            if ($company != "") {
                $customers = $customer_model->getCustomerByWhere(array('company_name'=>$company));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }
            else if ($mst != "") {
                $customers = $customer_model->getCustomerByWhere(array('mst'=>$mst));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }
            else if ($phone != "") {
                $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>$phone));
                if (!$customers) {
                    $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>str_replace(' ', '', $phone)));
                }
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }
            else if ($email != "") {
                $customers = $customer_model->getCustomerByWhere(array('customer_email'=>$email));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                        }
                    }
                    else{
                        $type = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$customers->customer_create_user,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                    );
                }
            }

            echo json_encode($arr);
        }
    }
    public function getcustomertire(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer_model = $this->model->get('customertireModel');
            
                if ($_POST['keyword'] == "*") {
                    $data = array(
                    'where'=>'( customer_tire_care = '.$_SESSION['userid_logined'].' )',
                    );
                    $list = $customer_model->getAllCustomer();
                }
                else{
                    $data = array(
                    'where'=>'( customer_tire_company LIKE "%'.$_POST['keyword'].'%" AND customer_tire_care = '.$_SESSION['userid_logined'].')',
                    );
                    $list = $customer_model->getAllCustomer($data);
                }
                
                $expect_date = "";

                foreach ($list as $rs) {
                    // put in bold the written text
                    $customer_name = $rs->customer_tire_company;
                    if ($_POST['keyword'] != "*") {
                        $customer_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->customer_tire_company);
                    }
                    // add new option
                    echo '<li onclick="set_item(\''.$rs->customer_tire_company.'\',\''.$rs->customer_tire_id.'\',\''.$rs->customer_tire_company.'\',\''.$rs->customer_tire_phone.'\',\''.($rs->customer_tire_street.', '.$rs->customer_tire_ward.', '.$rs->customer_tire_district.', '.$rs->customer_tire_city).'\',\''.$rs->customer_tire_email.'\',\''.$rs->customer_tire_contact.'\',\''.$rs->customer_tire_care.'\',\''.$expect_date.'\',\''.$rs->customer_tire_mst.'\',\''.($rs->customer_tire_type==1?1:2).'\')">'.$customer_name.'</li>';
                }
            
        }
    }
    public function getoldprice(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $customer = trim($_POST['customer']);
            $brand = trim($_POST['brand']);
            $size = trim($_POST['size']);
            $pattern = trim($_POST['pattern']);

            $price = array(
                'price' => "",
                'max' => 0,
                'going' => 0,
                'date' => null,
                'price_origin' => "",
                'discount_percent' => "",
                'discount_vnd' => "",
                );

            $ton = 0;
            $dangve = 0;

            $old = array();

            $tire_buy_model = $this->model->get('tirebuyModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $tire_going_model = $this->model->get('tiregoingModel');

            $old_order_tires = $order_tire_model->getAllTire(array('where'=>'customer='.$customer,'order_by'=>'delivery_date DESC','limit'=>1));
            foreach ($old_order_tires as $old_order_tire) {
                $price['discount_percent'] = $old_order_tire->discount_percent;
                $price['discount_vnd'] = $old_order_tire->discount_vnd;
            }

            $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$brand.' AND tire_buy_size = '.$size.' AND tire_buy_pattern = '.$pattern));
            foreach ($tire_buys as $tire) {
                $ton += $tire->tire_buy_volume;
                if ($tire->date_manufacture > 0) {
                    if (!in_array($tire->date_manufacture,$old)) {
                        $old[] = $tire->date_manufacture;
                        $price['date'] .= '<option value="'.$tire->date_manufacture.'">'.date('m/Y',$tire->date_manufacture).'</option>';
                    }
                    
                }
            }

            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_sales as $tire) {
                $ton -= $tire->volume;
            }

            $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
            foreach ($order_tires as $order) {
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
                foreach ($order_tire_lists as $list) {
                    $ton -= $list->tire_number;
                }
            }
            $price['max'] = $ton;

            $tire_goings = $tire_going_model->getAllTire(array('where'=>'(status IS NULL OR status != 1 ) AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_goings as $going) {
                $dangve += $going->tire_number;
            }
            $price['going'] = $dangve;

            $tire_price_discount_model = $this->model->get('tirepricediscountModel');
            $data_q = array(
                'where' => 'tire_brand ='.$brand.' AND tire_size ='.$size.' AND tire_pattern ='.$pattern.' AND start_date <= '.strtotime(date('d-m-Y')).' AND (end_date IS NULL OR end_date >= '.strtotime(date('d-m-Y')).')  ORDER BY start_date DESC LIMIT 1',
            );
            $prices = $tire_price_discount_model->getAllTire($data_q);
            foreach ($prices as $p) {
                $price['price_origin'] = $p->tire_price;
            }

            $sales = $tire_sale_model->queryTire('SELECT * FROM tire_sale WHERE customer = '.$customer.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern.' ORDER BY tire_sale_date DESC LIMIT 1');
            if ($sales) {
                foreach ($sales as $sale) {
                    if ($sale->sell_price_vat > 0) {
                        $price['price'] = $sale->sell_price_vat;
                    }
                    else{
                        $price['price'] = $sale->sell_price;
                    }
                    
                }
            }
            else{

                $price['price'] = $price['price_origin'];
                
            }


            echo json_encode($price);
        }
    }

    public function agentgetstock(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $brand = trim($_POST['brand']);
            $size = trim($_POST['size']);
            $pattern = trim($_POST['pattern']);

            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');

            $brand = $tire_brand_model->getTireByWhere(array('tire_brand_name'=>$brand))->tire_brand_id;
            $size = $tire_size_model->getTireByWhere(array('tire_size_number'=>$size))->tire_size_id;
            $pattern = $tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>$pattern))->tire_pattern_id;

            $price = array(
                'price' => "",
                'max' => 0,
                'going' => 0,
                'date' => null,
                );

            $ton = 0;
            $dangve = 0;

            $old = array();

            $tire_buy_model = $this->model->get('tirebuyModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $tire_going_model = $this->model->get('tiregoingModel');

            $tire_buys = $tire_buy_model->getAllTire(array('where'=>'tire_buy_brand = '.$brand.' AND tire_buy_size = '.$size.' AND tire_buy_pattern = '.$pattern));
            foreach ($tire_buys as $tire) {
                $ton += $tire->tire_buy_volume;
                if ($tire->date_manufacture > 0) {
                    if (!in_array($tire->date_manufacture,$old)) {
                        $old[] = $tire->date_manufacture;
                        $price['date'] .= '<option value="'.$tire->date_manufacture.'">'.date('m/Y',$tire->date_manufacture).'</option>';
                    }
                    
                }
            }

            $tire_sales = $tire_sale_model->getAllTire(array('where'=>'tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_sales as $tire) {
                $ton -= $tire->volume;
            }

            $order_tires = $order_tire_model->getAllTire(array('where'=>'(order_tire_status IS NULL OR order_tire_status = 0)'));
            foreach ($order_tires as $order) {
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$order->order_tire_id.' AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
                foreach ($order_tire_lists as $list) {
                    $ton -= $list->tire_number;
                }
            }
            $price['max'] = $ton;

            $tire_goings = $tire_going_model->getAllTire(array('where'=>'(status IS NULL OR status != 1 ) AND tire_brand = '.$brand.' AND tire_size = '.$size.' AND tire_pattern = '.$pattern));
            foreach ($tire_goings as $going) {
                $dangve += $going->tire_number;
            }
            $price['going'] = $dangve;

            


            echo json_encode($price);
        }
    }

    public function add(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $customer_model = $this->model->get('customerModel');
            $contact_person_model = $this->model->get('contactpersonModel');
            $customer_tire_model = $this->model->get('customertireModel');
            $tire_desrired_model = $this->model->get('tiredesiredModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_brands = $tire_brand_model->getAllTire();
            $data_brand = array();
            $id_order_tire = 0;
            foreach ($tire_brands as $tire) {
                $data_brand[$tire->tire_brand_id]['group'] = $tire->tire_brand_group;
            }

            if (isset($_POST['order_tire_waiting']) && $_POST['order_tire_waiting'] > 0) {
                $tire_waiting_model = $this->model->get('ordertirewaitingModel');

                $tire_waiting_model->updateTire(array('order_tire_waiting_status'=>1),array('order_tire_waiting_id'=>$_POST['order_tire_waiting']));
                $tire_desrired_model->updateTire(array('tire_desired_status'=>1),array('order_tire_waiting'=>$_POST['order_tire_waiting']));
            }

            if (trim($_POST['customer']) == "") {
                if (trim($_POST['customer_name']) != "") {
                    $cus = $customer_model->getCustomerByWhere(array('customer_name' => trim($_POST['customer_name'])));
                    if (!$cus) {
                        if (trim($_POST['mst']) != "") {
                            $cus = $customer_model->getCustomerByWhere(array('mst' => trim($_POST['mst'])));
                        }
                        if (!$cus) {
                            if (trim($_POST['phone']) != "") {
                                $cus = $customer_model->getCustomerByWhere(array('customer_phone' => trim($_POST['phone'])));
                                if (!$cus) {
                                    $cus = $customer_model->getCustomerByWhere(array('customer_phone' => str_replace(' ', '', trim($_POST['phone']))));
                                }
                            }
                        }
                    }
                    

                    if ($cus) {
                        $id_customer = $cus->customer_id;
                    }
                    else{
                        $data_cus = array(
                            'customer_name' => addslashes(trim($_POST['customer_name'])),
                            'company_name' => addslashes(trim($_POST['company'])),
                            'mst' => trim($_POST['mst']),
                            'customer_address' => addslashes(trim($_POST['address'])),
                            'customer_phone' => trim($_POST['phone']),
                            'customer_email' => trim($_POST['email']),
                            'customer_contact' => trim($_POST['contact']),
                            'customer_create_user' => trim($_POST['order_staff']),
                            'customer_tire_type' => $_POST['customer_type'],
                        );

                        $customer_model->createCustomer($data_cus);
                        $id_customer = $customer_model->getLastCustomer()->customer_id;

                        $data_contact_person = array(
                            'contact_person_name' => trim($_POST['contact']),
                            'contact_person_phone' => trim($_POST['phone']),
                            'contact_person_mobile' => trim($_POST['phone']),
                            'contact_person_email' => trim($_POST['email']),
                            'contact_person_birthday' => null,
                            'contact_person_address' => null,
                            'contact_person_position' => null,
                            'contact_person_department' => null,
                            'customer' => $id_customer,
                        );
                        $contact_person_model->createCustomer($data_contact_person);
                    }
                    
                }
            }
            else{
                $id_customer = trim($_POST['customer']);
                if ($_POST['customer_tire'] == 0) {
                    $customer_tire = $customer_tire_model->getCustomer($id_customer);

                    $data_cus = array(
                        'customer_name' => addslashes(trim($_POST['customer_name'])),
                        'company_name' => addslashes(trim($_POST['company'])),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => addslashes(trim($_POST['address'])),
                        'customer_phone' => trim($_POST['phone']),
                        'customer_email' => trim($_POST['email']),
                        'customer_contact' => trim($_POST['contact']),
                        'customer_create_user' => trim($_POST['order_staff']),
                        'customer_tire_type' => $_POST['customer_type'],
                        'customer_tire' => $id_customer,
                        'director' => $customer_tire->customer_tire_director,
                        'customer_fax' => $customer_tire->customer_tire_fax,
                        'customer_province' => $customer_tire->customer_tire_province,
                        'customer_street' => $customer_tire->customer_tire_street,
                        'customer_ward' => $customer_tire->customer_tire_ward,
                        'customer_district' => $customer_tire->customer_tire_district,
                        'customer_city' => $customer_tire->customer_tire_city,
                        'customer_ref' => $customer_tire->customer_tire_ref,
                        'customer_care' => $customer_tire->customer_tire_care,
                    );

                    $customer_model->createCustomer($data_cus);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;

                    $data_contact_person = array(
                        'contact_person_name' => trim($_POST['contact']),
                        'contact_person_phone' => trim($_POST['phone']),
                        'contact_person_mobile' => trim($_POST['phone']),
                        'contact_person_email' => trim($_POST['email']),
                        'contact_person_birthday' => null,
                        'contact_person_address' => null,
                        'contact_person_position' => null,
                        'contact_person_department' => null,
                        'customer' => $id_customer,
                    );
                    $contact_person_model->createCustomer($data_contact_person);
                }
                else{
                    if ($customer_model->getCustomerByWhere(array('customer_id'=>$id_customer,'customer_create_user'=>$_POST['order_staff']))) {
                        $data_cus = array(
                            'customer_name' => addslashes(trim($_POST['customer_name'])),
                            'company_name' => addslashes(trim($_POST['company'])),
                            'mst' => trim($_POST['mst']),
                            'customer_address' => addslashes(trim($_POST['address'])),
                            'customer_phone' => trim($_POST['phone']),
                            'customer_email' => trim($_POST['email']),
                            'customer_contact' => trim($_POST['contact']),
                            'customer_tire_type' => $_POST['customer_type'],
                        );

                        $customer_model->updateCustomer($data_cus,array('customer_id'=>$id_customer));
                    }
                }
                
            }

            $order_tire = $_POST['order_tire'];

            $data = array(
                'customer_type' => $_POST['customer_type'],
                'order_tire_date' => strtotime(date('d-m-Y')),
                'sale' => trim($_POST['order_staff']),
                'sale_cskh' => $_SESSION['userid_logined'],
                'customer' => $id_customer,
                'discount_percent' => trim(str_replace(',','',$_POST['discount_percent'])),
                'discount_vnd' => trim(str_replace(',','',$_POST['discount_vnd'])),
                'payment' => $_POST['payment'],
                'debt' => $_POST['debt'],
                'debt_number_day' => $_POST['debt_number_day'],
                'deposit' => trim(str_replace(',','',$_POST['deposit'])),
                'deposit_date' => strtotime($_POST['deposit_date']),
                'debt_1' => trim(str_replace(',','',$_POST['debt_1'])),
                'debt_1_date' => trim($_POST['debt_1_date'])!=""?strtotime($_POST['debt_1_date']):null,
                'debt_2' => trim(str_replace(',','',$_POST['debt_2'])),
                'debt_2_date' => trim($_POST['debt_2_date'])!=""?strtotime($_POST['debt_2_date']):null,
                'debt_3' => trim(str_replace(',','',$_POST['debt_3'])),
                'debt_3_date' => trim($_POST['debt_3_date'])!=""?strtotime($_POST['debt_3_date']):null,
                'ck_ttn' => $_POST['ck_ttn'],
                'ck_kho' => $_POST['ck_kho'],
                'ck_sl' => $_POST['ck_sl'],
                'discount' => trim(str_replace(',','',$_POST['discount'])),
                'reduce' => trim(str_replace(',','',$_POST['reduce'])),
                'vat_percent' => $_POST['vat_percent'],
                'vat' => trim(str_replace(',','',$_POST['vat'])),
                'warranty_percent' => $_POST['warranty_percent'],
                'warranty' => trim(str_replace(',','',$_POST['warranty'])),
                'delivery_date' => strtotime($_POST['delivery_date']),
                'due_date' => strtotime($_POST['due_date']),
                'total' => trim(str_replace(',','',$_POST['total'])),
                'order_tire_number' => $_POST['order_tire_number'],
                'order_tire_status' => 0,
                'check_price_vat' => $_POST['check_price_vat'],
            );

            $order_tire_model->createTire($data);
            $id_order_tire = $order_tire_model->getLastTire()->order_tire_id;

            if ($_POST['check_order'] == 1) {
                $tire_waiting_model = $this->model->get('ordertirewaitingModel');
                $data_waiting = array(
                    'order_tire_waiting_date' => strtotime(date('d-m-Y')),
                    'customer' => $id_customer,
                    'customer_type' => $_POST['customer_type'],
                    'sale' => trim($_POST['order_staff']),
                    'sale_cskh' => $_SESSION['userid_logined'],
                    'order_tire_waiting_number' => 0,
                );

                $tire_waiting_model->createTire($data_waiting);
                $id_waiting = $tire_waiting_model->getLastTire()->order_tire_waiting_id;

                $total_waiting = 0;
            }

            foreach ($order_tire as $v) {
                $data_order = array(
                    'tire_brand' => $v['tire_brand'],
                    'tire_size' => $v['tire_size'],
                    'tire_pattern' => $v['tire_pattern'],
                    'tire_number' => $v['max_number'] >= $v['tire_number'] ? $v['tire_number'] : $v['max_number'],
                    'tire_price' => trim(str_replace(',','',$v['tire_price'])),
                    'tire_price_vat' => $v['tire_price_vat'],
                    'order_tire' => $id_order_tire,
                    'tire_date' => $v['tire_date'],
                );

                $orders = $order_tire_list_model->getTireByWhere(array('tire_brand'=>$data_order['tire_brand'],'tire_size'=>$data_order['tire_size'],'tire_pattern'=>$data_order['tire_pattern'],'order_tire'=>$data_order['order_tire']));
                if ($orders) {
                    $order_tire_list_model->updateTire($data_order,array('order_tire_list_id'=>$orders->order_tire_list_id));
                }
                else{
                    $order_tire_list_model->createTire($data_order);
                }

                if ($_POST['check_order'] == 1) {
                    if ($v['max_number'] < $v['tire_number']) {
                        $tire_pattern = $tire_pattern_model->getTire($v['tire_pattern']);
                        $data_desired = array(
                            'tire_brand' => $data_brand[$v['tire_brand']]['group'],
                            'tire_brand_code' => $v['tire_brand'],
                            'tire_size' => $v['tire_size'],
                            'tire_pattern' => $tire_pattern->tire_pattern_type,
                            'tire_pattern_code' => $v['tire_pattern'],
                            'tire_number' => $v['tire_number'] - $v['max_number'],
                            'sale' => trim($_POST['order_staff']),
                            'sale_cskh' => $_SESSION['userid_logined'],
                            'tire_desired_date' => strtotime(date('d-m-Y')),
                            'tire_desired_priority' => 1,
                            'order_tire' => $id_order_tire,
                            'order_tire_waiting' => $id_waiting,
                            'tire_price' => trim(str_replace(',','',$v['tire_price'])),
                        );
                        $tire_desrired_model->createTire($data_desired);

                        $total_waiting += ($v['tire_number'] - $v['max_number']);
                    }
                    
                }
            }

            if ($_POST['check_order'] == 1) {
                $tire_waiting_model->updateTire(array('order_tire_waiting_number'=>$total_waiting),array('order_tire_waiting_id'=>$id_waiting));
            }

            echo $id_order_tire;

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$order_tire_model->getLastTire()->order_tire_id."|order_tire|".implode("-",$data)."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }

    public function agentgetcustomer(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_sale_model = $this->model->get('tiresaleModel');
            $customer_model = $this->model->get('customerModel');
            $staff_model = $this->model->get('staffModel');
            $company = isset($_POST['company'])?$_POST['company']:"";
            $mst = isset($_POST['mst'])?$_POST['mst']:"";
            $phone = isset($_POST['phone'])?$_POST['phone']:"";
            $email = isset($_POST['email'])?$_POST['email']:"";

            $arr = array(
                'company'=>"",
                'mst'=>"",
                'phone'=>"",
                'email'=>"",
                'type'=>"",
                'staff'=>"",
                'last_staff'=>"",
                'customer_name'=>"",
                'customer'=>"",
                'address'=>"",
                'contact'=>"",
                'approve'=>"",
            );

            $customers = null;

            if ($company != "") {
                $customers = $customer_model->getCustomerByWhere(array('company_name'=>$company));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                            $last_staff = $staff_model->getStaff($t->sale)->staff_name;
                            if ($t->tire_sale_date<strtotime(date("d-m-Y", strtotime("-6 months")))) {
                                $approve = 1;
                            }
                            else{
                                $approve = "";
                            }
                        }
                    }
                    else{
                        $type = 1;
                        $last_staff = "";
                        $approve = "";
                    }

                    if ($customers->customer_status==2) {
                        $approve = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$staff_model->getStaffByWhere(array('account'=>$customers->customer_create_user))->staff_name,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                        'last_staff'=>$last_staff,
                        'approve'=>$approve,
                    );
                }
            }
            if ($mst != "" && !$customers) {
                $customers = $customer_model->getCustomerByWhere(array('mst'=>$mst));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                            $last_staff = $staff_model->getStaff($t->sale)->staff_name;
                            if ($t->tire_sale_date<strtotime(date("d-m-Y", strtotime("-6 months")))) {
                                $approve = 1;
                            }
                            else{
                                $approve = "";
                            }
                        }
                    }
                    else{
                        $type = 1;
                        $last_staff = "";
                        $approve = "";
                    }

                    if ($customers->customer_status==2) {
                        $approve = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$staff_model->getStaffByWhere(array('account'=>$customers->customer_create_user))->staff_name,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                        'last_staff'=>$last_staff,
                        'approve'=>$approve,
                    );
                }
            }
            if ($phone != "" && !$customers) {
                $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>$phone));
                if (!$customers) {
                    $customers = $customer_model->getCustomerByWhere(array('customer_phone'=>str_replace(' ', '', $phone)));
                }
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                            $last_staff = $staff_model->getStaff($t->sale)->staff_name;
                            if ($t->tire_sale_date<strtotime(date("d-m-Y", strtotime("-6 months")))) {
                                $approve = 1;
                            }
                            else{
                                $approve = "";
                            }
                        }
                    }
                    else{
                        $type = 1;
                        $last_staff = "";
                        $approve = "";
                    }

                    if ($customers->customer_status==2) {
                        $approve = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$staff_model->getStaffByWhere(array('account'=>$customers->customer_create_user))->staff_name,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                        'last_staff'=>$last_staff,
                        'approve'=>$approve,
                    );
                }
            }
            if ($email != "" && !$customers) {
                $customers = $customer_model->getCustomerByWhere(array('customer_email'=>$email));
                if ($customers) {
                    $types = $tire_sale_model->getAllTire(array('where'=>'customer='.$customers->customer_id,'order_by'=>'tire_sale_date','order'=>'DESC','limit'=>1));
                    if ($types) {
                        foreach ($types as $t) {
                            $type = $t->customer_type;
                            $last_staff = $staff_model->getStaff($t->sale)->staff_name;
                            if ($t->tire_sale_date<strtotime(date("d-m-Y", strtotime("-6 months")))) {
                                $approve = 1;
                            }
                            else{
                                $approve = "";
                            }
                        }
                    }
                    else{
                        $type = 1;
                        $last_staff = "";
                        $approve = "";
                    }

                    if ($customers->customer_status==2) {
                        $approve = 1;
                    }

                    $arr = array(
                        'company'=>$customers->company_name,
                        'mst'=>$customers->mst,
                        'phone'=>$customers->customer_phone,
                        'email'=>$customers->customer_email,
                        'type'=>$type,
                        'staff'=>$staff_model->getStaffByWhere(array('account'=>$customers->customer_create_user))->staff_name,
                        'customer_name'=>$customers->customer_name,
                        'customer'=>$customers->customer_id,
                        'address'=>$customers->customer_address,
                        'contact'=>$customers->customer_contact,
                        'last_staff'=>$last_staff,
                        'approve'=>$approve,
                    );
                }
            }

            echo json_encode($arr);
        }
    }
    public function agentorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $customer_model = $this->model->get('customerModel');
            $tire_price_agent_model = $this->model->get('tirepriceagentModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            //$tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');
            
            $customers = $customer_model->getCustomerByWhere(array('customer_agent_link'=>$_POST['link_agent']));

            if ($customers) {
                $order_tire = $_POST['order_tire'];
                //$vat_check = trim($_POST['vat']);
                $vat_check = 1;

                $data = array(
                    'customer_type' => $customers->customer_tire_type,
                    'order_tire_date' => strtotime(date('d-m-Y')),
                    'sale' => $customers->customer_create_user,
                    'sale_cskh' => $customers->customer_create_user,
                    'customer' => $customers->customer_id,
                    'payment' => 1,
                    'debt' => null,
                    'debt_number_day' => null,
                    'deposit' => null,
                    'deposit_date' => null,
                    'debt_1' => null,
                    'debt_1_date' => null,
                    'debt_2' => null,
                    'debt_2_date' => null,
                    'debt_3' => null,
                    'debt_3_date' => null,
                    'ck_ttn' => 0,
                    'ck_kho' => 0,
                    'ck_sl' => 0,
                    'discount' => null,
                    'reduce' => null,
                    'vat_percent' => null,
                    'vat' => null,
                    'delivery_date' => null,
                    'due_date' => strtotime(date('d-m-Y')),
                    'total' => null,
                    'order_tire_number' => null,
                    'order_tire_status' => 0,
                    'check_price_vat' => 0,
                    'id_order_agent' => $_POST['id_order_tire'],
                    'sale_lock'=>1,
                    'order_cont' => $_POST['order_cont'],
                );

                if ($vat_check > 0) {
                    $data['vat_percent'] = 10;
                    $data['check_price_vat'] = 1;
                }

                $order_tire_model->createTire($data);
                $id_order_tire = $order_tire_model->getLastTire()->order_tire_id;

                $result = array(
                    'id_order_tire'=>$id_order_tire,
                    'total_number'=>0,
                    'total'=>0,
                    'item'=>array(),
                );

                $total_number = 0;
                $vat = 0;
                $total = 0;

                foreach ($order_tire as $v) {
                    $price_vat=0;
                    $price=0;

                    $brand = $tire_brand_model->getTireByWhere(array('tire_brand_name'=>$v['tire_brand']))->tire_brand_id;
                    $sizes = $tire_size_model->getTireByWhere(array('tire_size_number'=>$v['tire_size']));
                    $pattern = $tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>$v['tire_pattern']))->tire_pattern_id;

                    $size = $sizes->tire_size_id;
                    $size_num = explode('R', $sizes->tire_size_number)[0];

                    $ngay = strtotime(date('d-m-Y'));
                    $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' - 1 days')));
                    $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));

                    // $data_e = array(
                    //     'where' => 'tire_brand ='.$brand.' AND tire_size ='.$size.' AND tire_pattern ='.$pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                    //     'order_by' => 'start_date',
                    //     'order' => 'DESC',
                    //     'limit' => 1,
                    // );

                    // $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);

                    


                    $prices = $tire_price_agent_model->getAllTire(array('where'=>'customer='.$customers->customer_id.' AND tire_brand='.$brand.' AND tire_size='.$size.' AND tire_pattern='.$pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')  ORDER BY start_date DESC LIMIT 1'));
                    foreach ($prices as $p) {
                        $price_vat = $p->tire_price;
                        if ($data['order_cont']==1) {
                            $o = round(($price_vat*100/69)*0.68/1000)*1000;
                            $price_vat = $o;
                        }
                        // foreach ($tire_price_discount_events as $event) {
                        //     if ($event->percent_discount > 0) {
                        //         $price_vat = $p->tire_price*((100-$event->percent_discount)/100);
                        //     }
                        //     else{
                        //         $price_vat = $p->tire_price-$event->money_discount;
                        //     }
                        // }

                        // if ($vat_check == "" || $vat_check == null || $vat_check == 0) {
                        //     if ($price_vat < 5000000) {
                        //         $price_vat = $price_vat-160000;
                        //     }
                        //     else{
                        //         $price_vat = $price_vat-200000;
                        //     }
                        // }

                        // $price_vat = round($price_vat*0.995/1000)*1000;

                        // if (intval($size_num) > 10) {
                        // 	$price_vat = $price_vat-70000;
                        // }
                        // else{
                        // 	$price_vat = $price_vat-40000;
                        // }

                        // $price = $price_vat;
                        
                        if ($vat_check > 0) {
                            $pr = $price_vat;
                            $va = round(($pr*10*0.1)/1.1*0.1);
                            $n = $pr-$va;
                            $price = $n;
                            $vat += $va*$v['tire_number'];
                        }
                        
                    }

                    $data_order = array(
                        'tire_brand' => $brand,
                        'tire_size' => $size,
                        'tire_pattern' => $pattern,
                        'tire_number' => $v['tire_number'],
                        'tire_price' => $price,
                        'tire_price_vat' => $price_vat,
                        'order_tire' => $id_order_tire,
                        'tire_date' => null,
                    );

                    $orders = $order_tire_list_model->getTireByWhere(array('tire_brand'=>$data_order['tire_brand'],'tire_size'=>$data_order['tire_size'],'tire_pattern'=>$data_order['tire_pattern'],'order_tire'=>$data_order['order_tire']));
                    if ($orders) {
                        $order_tire_list_model->updateTire($data_order,array('order_tire_list_id'=>$orders->order_tire_list_id));
                    }
                    else{
                        $order_tire_list_model->createTire($data_order);
                    }

                    $total_number += $data_order['tire_number'];
                    $total += $data_order['tire_price_vat']*$data_order['tire_number'];

                    $result['item'][] = array(
                        'tire_brand' => $tire_brand_model->getTire($brand)->tire_brand_name,
                        'tire_size' => $tire_size_model->getTire($size)->tire_size_number,
                        'tire_pattern' => $tire_pattern_model->getTire($pattern)->tire_pattern_name,
                        'tire_number' => $data_order['tire_number'],
                        'tire_price' => $price_vat,
                    );
                }

                $data_order = array(
                    'total'=>$total,
                    'order_tire_number'=>$total_number,
                    'vat'=> $vat,
                );


                $order_tire_model->updateTire($data_order,array('order_tire_id'=>$id_order_tire));

                $result['total'] = $total;
                $result['total_number'] = $total_number;

                echo json_encode($result);

                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                $filename = "action_logs.txt";
                $text = date('d/m/Y H:i:s')."|".$_POST['link_agent']."|"."agent_order"."|".$order_tire_model->getLastTire()->order_tire_id."|order_tire|".implode("-",$data)."\n"."\r\n";
                
                $fh = fopen($filename, "a") or die("Could not open log file.");
                fwrite($fh, $text) or die("Could not write file!");
                fclose($fh);
            }

            
        }
    }

    public function editagentorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            $customer_model = $this->model->get('customerModel');
            $tire_price_agent_model = $this->model->get('tirepriceagentModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');
            //$tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');

            $customers = $customer_model->getCustomerByWhere(array('customer_agent_link'=>$_POST['link_agent']));

            if ($customers) {
                $order = trim($_POST['id_order_tire']);
                $brand = $tire_brand_model->getTireByWhere(array('tire_brand_name'=>$_POST['tire_brand']))->tire_brand_id;
                $pattern = $tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>$_POST['tire_pattern']))->tire_pattern_id;
                $sizes = $tire_size_model->getTireByWhere(array('tire_size_number'=>$_POST['tire_size']));
                $size = $sizes->tire_size_id;
                $size_num = explode('R', $sizes->tire_size_number)[0];

                $number = trim($_POST['tire_number']);

                $data = array(
                    'tire_brand'=>$brand,
                    'tire_pattern'=>$pattern,
                    'tire_size'=>$size,
                    'tire_number'=>$number,
                );

                $order_tire = $order_tire_model->getTireByWhere(array('id_order_agent'=>$order));
                $vat_check = $order_tire->vat_percent;

                if ($order_tire->order_tire_status!=1) {
                    if ($_POST['update']==1) {
                        $brand_old = $tire_brand_model->getTireByWhere(array('tire_brand_name'=>$_POST['tire_brand_old']))->tire_brand_id;
                        $pattern_old = $tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>$_POST['tire_pattern_old']))->tire_pattern_id;
                        $size_old = $tire_size_model->getTireByWhere(array('tire_size_number'=>$_POST['tire_size_old']))->tire_size_id;

                        $order_tire_list = $order_tire_list_model->getTireByWhere(array('order_tire'=>$order_tire->order_tire_id,'tire_brand'=>$brand_old,'tire_size'=>$size_old,'tire_pattern'=>$pattern_old));
                    }
                    else{
                        $order_tire_list = $order_tire_list_model->getTireByWhere(array('order_tire'=>$order_tire->order_tire_id,'tire_brand'=>$brand,'tire_size'=>$size,'tire_pattern'=>$pattern));
                    }

                    

                    if ($order_tire_list) {
                        $ngay = strtotime(date('d-m-Y'));
                        $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' - 1 days')));
                        $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));

                        // $data_e = array(
                        //     'where' => 'tire_brand ='.$brand.' AND tire_size ='.$size.' AND tire_pattern ='.$pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                        //     'order_by' => 'start_date',
                        //     'order' => 'DESC',
                        //     'limit' => 1,
                        // );

                        // $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);


                        $prices = $tire_price_agent_model->getAllTire(array('where'=>'customer='.$customers->customer_id.' AND tire_brand='.$brand.' AND tire_size='.$size.' AND tire_pattern='.$pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')  ORDER BY start_date DESC LIMIT 1'));

                        foreach ($prices as $p) {
                            $price_vat = $p->tire_price;
                            if ($order_tire->order_cont==1) {
                                $o = round(($price_vat*100/69)*0.68/1000)*1000;
                                $price_vat = $o;
                            }
                            // foreach ($tire_price_discount_events as $event) {
                            //     if ($event->percent_discount > 0) {
                            //         $price_vat = $p->tire_price*((100-$event->percent_discount)/100);
                            //     }
                            //     else{
                            //         $price_vat = $p->tire_price-$event->money_discount;
                            //     }
                            // }

                            // if ($vat_check == "" || $vat_check == null || $vat_check == 0) {
                            //     if ($price_vat < 5000000) {
                            //         $price_vat = $price_vat-160000;
                            //     }
                            //     else{
                            //         $price_vat = $price_vat-200000;
                            //     }
                            // }

                            // $price_vat = round($price_vat*0.995/1000)*1000;

                            // if (intval($size_num) > 10) {
                            //     $price_vat = $price_vat-70000;
                            // }
                            // else{
                            //     $price_vat = $price_vat-40000;
                            // }

                            // $price = $price_vat;
                            
                            if ($vat_check > 0) {
                                $pr = $price_vat;
                                $va = round(($pr*10*0.1)/1.1*0.1);
                                $n = $pr-$va;
                                $price = $n;
                            }

                            
                        }

                        $data['tire_price'] = $price;
                        $data['tire_price_vat'] = $price_vat;

                        $order_tire_list_model->updateTire($data,array('order_tire_list_id'=>$order_tire_list->order_tire_list_id));

                        $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                        $total_number = 0;
                        $total = 0;
                        $vat = 0;
                        foreach ($order_lists as $od) {
                            $total_number += $od->tire_number;
                            
                            if ($order_tire->check_price_vat == 1) {
                                $p = $od->tire_price_vat;
                                $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                                $n = $p-$v;

                                $vat += $v*$od->tire_number;
                                $total += $od->tire_number*$od->tire_price_vat;
                            }
                            else{
                                $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                                $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            }
                        }

                        $discount = $order_tire->discount+$order_tire->reduce;
                        $warranty = round($total*$order_tire->warranty_percent/100);
                        $total = $total - $discount - $warranty;


                        $data_order = array(
                            'discount'=>$discount,
                            'total'=>$total,
                            'order_tire_number'=>$total_number,
                            'vat'=> $vat,
                            'warranty'=>$warranty,
                        );


                        $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                        if($order_tire->order_tire_status==1){
                            $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                            $order_tire_list_old = $order_tire_list_model->getTire($order_tire_list->order_tire_list_id);

                            $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                            $tire_sale = $tire_sale_model->getTireByWhere(array('tire_brand'=>$order_tire_list->tire_brand,'tire_size'=>$order_tire_list->tire_size,'tire_pattern'=>$order_tire_list->tire_pattern,'order_tire'=>$order_tire_list->order_tire));
                            $data_sale = array(
                                'tire_brand'=>$order_tire_list_old->tire_brand,
                                'tire_size'=>$order_tire_list_old->tire_size,
                                'tire_pattern'=>$order_tire_list_old->tire_pattern,
                                'volume' => $order_tire_list_old->tire_number,
                                'sell_price' => $order_tire_list_old->tire_price,
                                'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                                'date_manufacture_sale' => $order_tire_list_old->tire_date,
                            );
                            $tire_sale_model->updateTire($data_sale,array('tire_sale_id'=>$tire_sale->tire_sale_id));

                            $obtain_data = array(
                                'obtain_date' => $order_tire_old->delivery_date,
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'order_tire' => $order_tire_list->order_tire,
                            );
                            if($obtain_data['week'] == 53){
                                $obtain_data['week'] = 1;
                                $obtain_data['year'] = $obtain_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }
                            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                            $receivable_data = array(
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'receivable_date' => $order_tire_old->delivery_date,
                                'expect_date' => $order_tire_old->delivery_date,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'code' => $order_tire_old->order_number,
                                'source' => 1,
                                'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                                'type' => 4,
                                'order_tire' => $order_tire_list->order_tire,
                                'check_vat' => $order_tire_old->vat>0?1:0,
                            );

                            
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }

                            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                        }

                        

                                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                                    $filename = "action_logs.txt";
                                    $text = date('d/m/Y H:i:s')."|".$order_tire->customer."|"."editagent"."|".$order_tire_list->order_tire_list_id."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                                    
                                    $fh = fopen($filename, "a") or die("Could not open log file.");
                                    fwrite($fh, $text) or die("Could not write file!");
                                    fclose($fh);
                    }
                    else{
                        $price = 0;
                        $price_vat = 0;

                        $data['order_tire'] = $order_tire->order_tire_id;

                        $ngay = strtotime(date('d-m-Y'));
                        $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' - 1 days')));
                        $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));

                        // $data_e = array(
                        //     'where' => 'tire_brand ='.$brand.' AND tire_size ='.$size.' AND tire_pattern ='.$pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                        //     'order_by' => 'start_date',
                        //     'order' => 'DESC',
                        //     'limit' => 1,
                        // );

                        // $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);


                        $prices = $tire_price_agent_model->getAllTire(array('where'=>'customer='.$customers->customer_id.' AND tire_brand='.$brand.' AND tire_size='.$size.' AND tire_pattern='.$pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')  ORDER BY start_date DESC LIMIT 1'));

                        foreach ($prices as $p) {
                            $price_vat = $p->tire_price;
                            if ($order_tire->order_cont==1) {
                                $o = round(($price_vat*100/69)*0.68/1000)*1000;
                                $price_vat = $o;
                            }
                            // foreach ($tire_price_discount_events as $event) {
                            //     if ($event->percent_discount > 0) {
                            //         $price_vat = $p->tire_price*((100-$event->percent_discount)/100);
                            //     }
                            //     else{
                            //         $price_vat = $p->tire_price-$event->money_discount;
                            //     }
                            // }

                            // if ($vat_check == "" || $vat_check == null || $vat_check == 0) {
                            //     if ($price_vat < 5000000) {
                            //         $price_vat = $price_vat-160000;
                            //     }
                            //     else{
                            //         $price_vat = $price_vat-200000;
                            //     }
                            // }

                            // $price_vat = round($price_vat*0.995/1000)*1000;

                            // if (intval($size_num) > 10) {
                            //     $price_vat = $price_vat-70000;
                            // }
                            // else{
                            //     $price_vat = $price_vat-40000;
                            // }

                            // $price = $price_vat;
                            
                            if ($vat_check > 0) {
                                $pr = $price_vat;
                                $va = round(($pr*10*0.1)/1.1*0.1);
                                $n = $pr-$va;
                                $price = $n;
                            }

                            
                        }

                        $data['tire_price'] = $price;
                        $data['tire_price_vat'] = $price_vat;

                        $order_tire_list_model->createTire($data);

                        $order_tire_list = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                        $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                        $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                        $total_number = 0;
                        $total = 0;
                        $vat = 0;
                        foreach ($order_lists as $od) {
                            $total_number += $od->tire_number;
                            
                            if ($order_tire->check_price_vat == 1) {
                                $p = $od->tire_price_vat;
                                $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                                $n = $p-$v;

                                $vat += $v*$od->tire_number;
                                $total += $od->tire_number*$od->tire_price_vat;
                            }
                            else{
                                $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                                $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            }
                        }

                        $discount = $order_tire->discount+$order_tire->reduce;
                        $warranty = round($total*$order_tire->warranty_percent/100);
                        $total = $total - $discount - $warranty;

                        $data_order = array(
                            'discount'=>$discount,
                            'total'=>$total,
                            'order_tire_number'=>$total_number,
                            'vat'=> $vat,
                            'warranty'=>$warranty,
                        );


                        $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                        if($order_tire->order_tire_status==1){

                            $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                            $order_tire_list_old = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                            $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                            $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire_old->sale));

                            $check_vat = $order_tire_old->vat>0?1:0;
                            //$vat = $order->tire_price*$order_tire->vat_percent/100;
                            $data_sale = array(
                                    
                                'code' => $order_tire_old->order_number,
                                'volume' => $order_tire_list_old->tire_number,
                                'tire_brand' => $order_tire_list_old->tire_brand,
                                'tire_size' => $order_tire_list_old->tire_size,
                                'sell_price' => $order_tire_list_old->tire_price,
                                'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                                'customer' => $order_tire_old->customer,
                                'tire_sale_date' => $order_tire_old->delivery_date,
                                //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                                'tire_pattern' => $order_tire_list_old->tire_pattern,
                                'check_vat' => $check_vat,
                                'sale' => $staff->staff_id,
                                'customer_type' => $order_tire_old->customer_type,
                                'order_tire' => $order_tire_list->order_tire,
                                'date_manufacture_sale' => $order_tire_list_old->tire_date,
                            );
                            $tire_sale_model->createTire($data_sale);

                            $obtain_data = array(
                                'obtain_date' => $order_tire_old->delivery_date,
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'order_tire' => $order_tire_list->order_tire,
                            );
                            if($obtain_data['week'] == 53){
                                $obtain_data['week'] = 1;
                                $obtain_data['year'] = $obtain_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }
                            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                            $receivable_data = array(
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'receivable_date' => $order_tire_old->delivery_date,
                                'expect_date' => $order_tire_old->delivery_date,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'code' => $order_tire_old->order_number,
                                'source' => 1,
                                'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                                'type' => 4,
                                'order_tire' => $order_tire_list->order_tire,
                                'check_vat' => $order_tire_old->vat>0?1:0,
                            );

                            
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }

                            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                        }

                        

                                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                                    $filename = "action_logs.txt";
                                    $text = date('d/m/Y H:i:s')."|".$order_tire->customer."|"."addagent"."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                                    
                                    $fh = fopen($filename, "a") or die("Could not open log file.");
                                    fwrite($fh, $text) or die("Could not write file!");
                                    fclose($fh);
                    }
                }

                

                

                $order_tire = $order_tire_model->getTireByWhere(array('id_order_agent'=>$order));

                $result = array(
                    'id_order_tire'=>$order_tire->order_tire_id,
                    'total_number'=>$total_number,
                    'total'=>$total,
                    'item'=>array(),
                );

                $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire->order_tire_id));
                foreach ($order_lists as $order_list) {
                    $result['item'][] = array(
                        'tire_brand' => $tire_brand_model->getTire($order_list->tire_brand)->tire_brand_name,
                        'tire_size' => $tire_size_model->getTire($order_list->tire_size)->tire_size_number,
                        'tire_pattern' => $tire_pattern_model->getTire($order_list->tire_pattern)->tire_pattern_name,
                        'tire_number' => $order_list->tire_number,
                        'tire_price' => $order_list->tire_price_vat,
                    );
                }

                echo json_encode($result);
                

            }
            
            
            

        }
    }

    public function deleteagentorder(){
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $customer_model = $this->model->get('customerModel');
            $tire_price_agent_model = $this->model->get('tirepriceagentModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_size_model = $this->model->get('tiresizeModel');

            $customers = $customer_model->getCustomerByWhere(array('customer_agent_link'=>$_POST['link_agent']));

            if ($customers) {
                $order = trim($_POST['id_order_tire']);
                $brand = $tire_brand_model->getTireByWhere(array('tire_brand_name'=>$_POST['tire_brand']))->tire_brand_id;
                $pattern = $tire_pattern_model->getTireByWhere(array('tire_pattern_name'=>$_POST['tire_pattern']))->tire_pattern_id;
                $size = $tire_size_model->getTireByWhere(array('tire_size_number'=>$_POST['tire_size']))->tire_size_id;

                $order_tire = $order_tire_model->getTireByWhere(array('id_order_agent'=>$order));

                if ($order_tire->order_tire_status!=1) {
                    $order_tire_list = $order_tire_list_model->getTireByWhere(array('order_tire'=>$order_tire->order_tire_id,'tire_brand'=>$brand,'tire_size'=>$size,'tire_pattern'=>$pattern));

                    $total_number = $order_tire->order_tire_number;
                    $total = $order_tire->total;
                    $total_after = $order_tire_list->tire_number*$order_tire_list->tire_price;
                    $vat = $order_tire->vat;
                    $discount = $order_tire->discount;

                    $total_number = $total_number - $order_tire_list->tire_number;
                    $total = $total - $total_after;

                    /*if ($order_tire->ck_ttn==1) {
                        $discount = $discount - ($total_after*0.02);
                        $total = $total + ($total_after*0.02);
                    }
                    if ($order_tire->ck_kho==1) {
                        $discount = $discount - ($order_tire_list->tire_number*100000);
                        $total = $total + ($order_tire_list->tire_number*100000);
                    }
                    if ($order_tire->ck_sl==1) {
                        if ($order_tire->order_tire_number >= 20 && $order_tire->order_tire_number < 50) {
                            $discount = $discount - ($total_after*0.01);
                            $total = $total + ($total_after*0.01);
                        }
                        else if ($order_tire->order_tire_number >= 50 && $order_tire->order_tire_number < 100) {
                            $discount = $discount - ($total_after*0.02);
                            $total = $total + ($total_after*0.02);
                        }
                        else if ($order_tire->order_tire_number >= 100) {
                            $discount = $discount - ($total_after*0.03);
                            $total = $total + ($total_after*0.03);
                        }
                    }*/

                    if ($order_tire->vat_percent > 0) {
                        $vat = $vat - ($total_after*$order_tire->vat_percent/100);
                        $total = $total - ($total_after*$order_tire->vat_percent/100);
                    }

                    

                    $data_order = array(
                        'discount'=>$discount,
                        'total'=>$total,
                        'order_tire_number'=>$total_number,
                        'vat'=> $vat,
                    );


                    $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                    if($order_tire->order_tire_status==1){

                        $order_tire_list_old = $order_tire_list_model->getTire($order_tire_list->order_tire_list_id);

                        $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                        $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE tire_brand = '.$order_tire_list_old->tire_brand.' AND tire_size = '.$order_tire_list_old->tire_size.' AND tire_pattern = '.$order_tire_list_old->tire_pattern.' AND order_tire = '.$order_tire_list->order_tire);

                        $obtain_data = array(
                            'obtain_date' => $order_tire_old->delivery_date,
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'order_tire' => $order_tire_list->order_tire,
                        );
                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }
                        $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                        $receivable_data = array(
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'receivable_date' => $order_tire_old->delivery_date,
                            'expect_date' => $order_tire_old->delivery_date,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'code' => $order_tire_old->order_number,
                            'source' => 1,
                            'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                            'type' => 4,
                            'order_tire' => $order_tire_list->order_tire,
                            'check_vat' => $order_tire_old->vat>0?1:0,
                        );

                        
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }

                        $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                    }

                    $order_tire_list_model->deleteTire($order_tire_list->order_tire_list_id);
                    
                    $result = array(
                        'id_order_tire'=>$order_tire->order_tire_id,
                        'total_number'=>$total_number,
                        'total'=>$total,
                        'item'=>array(),
                        'accept'=>1,
                    );

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$customers->customer_id."|"."deleteagentorder"."|".$order_tire_list->order_tire_list_id."|order_tire_list|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                    
                }
                else{
                    $result = array(
                        'id_order_tire'=>$order_tire->order_tire_id,
                        'total_number'=>$order_tire->order_tire_number,
                        'total'=>$order_tire->total,
                        'item'=>array(),
                        'accept'=>0,
                    );
                }
                

                echo json_encode($result);
            }
            
            
        }
    }

    public function agentorderdelete(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_cost_model = $this->model->get('ordertirecostModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $receivable_model = $this->model->get('receivableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $lift = $this->model->get('liftModel');
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
            $additional_model = $this->model->get('additionalModel');
            $customer_model = $this->model->get('customerModel');

            $customers = $customer_model->getCustomerByWhere(array('customer_agent_link'=>$_POST['link_agent']));

            if ($customers) {

                $result = array(
                    'accept'=>0,
                );

                $order_data = $order_tire_model->getTireByWhere(array('customer'=>$customers->customer_id,'id_order_agent'=>$_POST['id_order_tire']));

                if ($order_data->order_tire_status!=1) {
                    $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$order_data->order_tire_id));
                    foreach ($re as $r) {
                        $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                        $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                    }
                    $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$order_data->order_tire_id));
                    foreach ($pa as $p) {
                        $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                        $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                    }

                    $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$order_data->order_tire_id);
                    $payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$order_data->order_tire_id);
                    $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$order_data->order_tire_id);
                    $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$order_data->order_tire_id);
                    $order_tire_list_model->queryTire('DELETE FROM order_tire_list WHERE order_tire = '.$order_data->order_tire_id);
                    $order_tire_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire = '.$order_data->order_tire_id);
                    $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$order_data->order_tire_id);
                    $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$order_data->order_tire_id);
                    $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE order_tire = '.$order_data->order_tire_id);
                    $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE order_tire = '.$order_data->order_tire_id);
                    $additional_model->queryAdditional('DELETE FROM additional WHERE order_tire = '.$order_data->order_tire_id);
                    $order_tire_model->deleteTire($order_data->order_tire_id);
                    
                    $result = array(
                        'accept'=>1,
                    );
                    
                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$order_data->customer."|"."deleteorderagent"."|".$order_data->order_tire_id."|order_tire|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                    
                }

                echo json_encode($result);
                
            }
            
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_cost_model = $this->model->get('ordertirecostModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $receivable_model = $this->model->get('receivableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            $pay = $this->model->get('payModel');
            $lift = $this->model->get('liftModel');
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
            $additional_model = $this->model->get('additionalModel');
            $account_balance_model = $this->model->get('accountbalanceModel');
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                        //$order_data = $order_tire_model->getTire($data);

                        $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$data));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$data));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$data);
                        $payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$data);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$data);
                        $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$data);
                        $order_tire_list_model->queryTire('DELETE FROM order_tire_list WHERE order_tire = '.$data);
                        $order_tire_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire = '.$data);
                        $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$data);
                        $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$data);
                        $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE order_tire = '.$data);
                        $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE order_tire = '.$data);
                        $additionals = $additional_model->getAllAdditional(array('where'=>'order_tire = '.$data));
                        foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                        }
                        $order_tire_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        //$order_data = $order_tire_model->getTire($_POST['data']);

                        $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
                        foreach ($re as $r) {
                            $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                            $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
                        }
                        $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
                        foreach ($pa as $p) {
                            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
                            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
                        }

                        $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$_POST['data']);
                        $payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$_POST['data']);
                        $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$_POST['data']);
                        $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$_POST['data']);
                        $order_tire_list_model->queryTire('DELETE FROM order_tire_list WHERE order_tire = '.$_POST['data']);
                        $order_tire_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire = '.$_POST['data']);
                        $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$_POST['data']);
                        $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$_POST['data']);
                        $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE order_tire = '.$_POST['data']);
                        $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE order_tire = '.$_POST['data']);
                        $additional_model->queryAdditional('DELETE FROM additional WHERE order_tire = '.$_POST['data']);
                        $additionals = $additional_model->getAllAdditional(array('where'=>'order_tire = '.$_POST['data']));
                        foreach ($additionals as $add) {
                           $additional_model->deleteAdditional($add->additional_id);
                           $account_balance_model->queryAccount("DELETE FROM account_balance WHERE additional = ".$add->additional_id);
                        }
                        $order_tire_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function add_desired(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_desrired_model = $this->model->get('tiredesiredModel');
            $tire_pattern_model = $this->model->get('tirepatternModel');
            $tire_waiting_model = $this->model->get('ordertirewaitingModel');
            $customer_model = $this->model->get('customerModel');
            $customer_tire_model = $this->model->get('customertireModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_brands = $tire_brand_model->getAllTire();
            $data_brand = array();
            foreach ($tire_brands as $tire) {
                $data_brand[$tire->tire_brand_id]['group'] = $tire->tire_brand_group;
            }

            if (isset($_POST['order_tire_waiting']) && $_POST['order_tire_waiting'] > 0) {
                $tire_waiting_model = $this->model->get('ordertirewaitingModel');

                $tire_waiting_model->updateTire(array('order_tire_waiting_status'=>1),array('order_tire_waiting_id'=>$_POST['order_tire_waiting']));
                $tire_desrired_model->updateTire(array('tire_desired_status'=>1),array('order_tire_waiting'=>$_POST['order_tire_waiting']));
            }

            if (trim($_POST['customer']) == "") {
                if (trim($_POST['customer_name']) != "") {
                    $data_cus = array(
                        'customer_name' => addslashes(trim($_POST['customer_name'])),
                        'company_name' => addslashes(trim($_POST['company'])),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => addslashes(trim($_POST['address'])),
                        'customer_phone' => trim($_POST['phone']),
                        'customer_email' => trim($_POST['email']),
                        'customer_contact' => trim($_POST['contact']),
                    );

                    $customer_model->createCustomer($data_cus);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;
                }
            }
            else{
                $id_customer = trim($_POST['customer']);
                if ($_POST['customer_tire'] == 0) {
                    $customer_tire = $customer_tire_model->getCustomer($id_customer);

                    $data_cus = array(
                        'customer_name' => addslashes(trim($_POST['customer_name'])),
                        'company_name' => addslashes(trim($_POST['company'])),
                        'mst' => trim($_POST['mst']),
                        'customer_address' => addslashes(trim($_POST['address'])),
                        'customer_phone' => trim($_POST['phone']),
                        'customer_email' => trim($_POST['email']),
                        'customer_contact' => trim($_POST['contact']),
                        'customer_create_user' => trim($_POST['order_staff']),
                        'customer_tire' => $id_customer,
                        'director' => $customer_tire->customer_tire_director,
                        'customer_fax' => $customer_tire->customer_tire_fax,
                        'customer_province' => $customer_tire->customer_tire_province,
                        'customer_street' => $customer_tire->customer_tire_street,
                        'customer_ward' => $customer_tire->customer_tire_ward,
                        'customer_district' => $customer_tire->customer_tire_district,
                        'customer_city' => $customer_tire->customer_tire_city,
                        'customer_ref' => $customer_tire->customer_tire_ref,
                        'customer_care' => $customer_tire->customer_tire_care,
                    );

                    $customer_model->createCustomer($data_cus);
                    $id_customer = $customer_model->getLastCustomer()->customer_id;
                }
                else{
                    if ($customer_model->getCustomerByWhere(array('customer_id'=>$id_customer,'customer_create_user'=>$_POST['order_staff']))) {
                        $data_cus = array(
                            'customer_name' => addslashes(trim($_POST['customer_name'])),
                            'company_name' => addslashes(trim($_POST['company'])),
                            'mst' => trim($_POST['mst']),
                            'customer_address' => addslashes(trim($_POST['address'])),
                            'customer_phone' => trim($_POST['phone']),
                            'customer_email' => trim($_POST['email']),
                            'customer_contact' => trim($_POST['contact']),
                        );

                        $customer_model->updateCustomer($data_cus,array('customer_id'=>$id_customer));
                    }
                }
            }

            $data_waiting = array(
                'order_tire_waiting_date' => strtotime(date('d-m-Y')),
                'customer' => $id_customer,
                'customer_type' => $_POST['customer_type'],
                'sale' => trim($_POST['order_staff']),
                'sale_cskh' => $_SESSION['userid_logined'],
                'order_tire_waiting_number' => $_POST['total_number'],
            );

            $tire_waiting_model->createTire($data_waiting);
            $id_waiting = $tire_waiting_model->getLastTire()->order_tire_waiting_id;

            $order_tire = $_POST['order_tire'];

            foreach ($order_tire as $v) {
                $tire_pattern = $tire_pattern_model->getTire($v['tire_pattern']);
                $data_desired = array(
                    'tire_brand' => $data_brand[$v['tire_brand']]['group'],
                    'tire_brand_code' => $v['tire_brand'],
                    'tire_size' => $v['tire_size'],
                    'tire_pattern' => $tire_pattern->tire_pattern_type,
                    'tire_pattern_code' => $v['tire_pattern'],
                    'tire_number' => $v['tire_number'],
                    'sale' => trim($_POST['order_staff']),
                    'sale_cskh' => $_SESSION['userid_logined'],
                    'tire_desired_date' => strtotime(date('d-m-Y')),
                    'tire_desired_priority' => 1,
                    'order_tire_waiting' => $id_waiting,
                    'tire_price' => trim(str_replace(',','',$v['tire_price'])),
                );
                $tire_desrired_model->createTire($data_desired);
            }

            echo "Đặt hàng thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$id_waiting."|tire_waiting|".implode("-",$data_waiting)."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }

    public function unlock(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $order = trim($_POST['data']);
            $lock = trim($_POST['val']);
            $order_tire_model->updateTire(array('sale_lock'=>$lock),array('order_tire_id'=>$order));

            echo "Thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."lock"."|".$order."|ordertire|".$lock."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }

    public function getDetailOrder(){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $id = $_GET['order'];
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire = $order_tire_model->getTire($id);

        $customer_model = $this->model->get('customerModel');
        $customers = $customer_model->getCustomer($order_tire->customer);

        $staff_model = $this->model->get('staffModel');
        $sale = $staff_model->getStaffByWhere(array('account'=>$order_tire->sale))->staff_name;
        
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand = tire_brand_id AND tire_size = tire_size_id AND tire_pattern = tire_pattern_id');

        $data = array(
            'where' => 'order_tire='.$id,
        );

        /*if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8) {
            $data = array(
                'where' => 'order_tire IN (SELECT order_tire_id FROM order_tire WHERE order_tire_id ='.$id.' AND sale = '.$_SESSION["userid_logined"].')',
            );
        }*/

        $order_tire_lists = $order_tire_list_model->getAllTire($data,$join);
        $this->view->data['order_tire_lists'] = $order_tire_lists;

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');
        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $price = array();
        foreach ($order_tire_lists as $order) {
            $ngay = $order_tire->delivery_date>0?$order_tire->delivery_date:strtotime(date('d-m-Y'));
            $prices = $tire_price_discount_model->getAllTire(array('where'=>'tire_brand='.$order->tire_brand.' AND tire_size='.$order->tire_size.' AND tire_pattern='.$order->tire_pattern.' AND start_date <= '.$ngay.' AND (end_date IS NULL OR end_date >= '.$ngay.')  ORDER BY start_date DESC LIMIT 1'));
            foreach ($prices as $p) {
                $price[$order->order_tire_list_id] = $p->tire_price;
            }
        }

        

        $this->view->data['price'] = $price;
        $this->view->data['order'] = $id;
        $this->view->data['order_tire'] = $order_tire;
        $this->view->data['customers'] = $customers;
        $this->view->data['sale'] = $sale;

        $this->view->show('ordertire/detailorder');
    }

    public function listtire($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $ketthuc = date('d-m-Y',$this->registry->router->order);
        $cus = $this->registry->router->page;
        $previous_url = $this->registry->router->order_by;
        if ($cus > 0 && $previous_url != "") {
            $this->view->data['previous_url'] = $previous_url.'/'.$cus.'/'.strtotime($ketthuc);
        }

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire = $order_tire_model->getTire($id);
        
        $order_tire_list_model = $this->model->get('ordertirelistModel');
        $join = array('table'=>'tire_brand,tire_size,tire_pattern','where'=>'tire_brand = tire_brand_id AND tire_size = tire_size_id AND tire_pattern = tire_pattern_id');

        $data = array(
            'where' => 'order_tire='.$id,
        );

        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 9 && $_SESSION['role_logined'] != 8 && $_SESSION['role_logined'] != 2) {
            $data = array(
                'where' => 'order_tire IN (SELECT order_tire_id FROM order_tire WHERE order_tire_id ='.$id.' AND sale = '.$_SESSION["userid_logined"].')',
            );
        }

        $order_tire_lists = $order_tire_list_model->getAllTire($data,$join);
        $this->view->data['order_tire_lists'] = $order_tire_lists;

        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');
        $tire_quotation_model = $this->model->get('tirequotationModel');
        $tire_quotation_brand_model = $this->model->get('tirequotationbrandModel');
        $tire_quotation_size_model = $this->model->get('tirequotationsizeModel');

        $tire_price_discount_model = $this->model->get('tirepricediscountModel');
        $tire_price_discount_event_model = $this->model->get('tirepricediscounteventModel');

        $price = array();
        foreach ($order_tire_lists as $order) {
            $ngay = $order_tire->delivery_date>0?$order_tire->delivery_date:$order_tire->order_tire_date;

            $ngaytruoc = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' - 1 days')));
            $ngaysau = strtotime(date('d-m-Y', strtotime(date('d-m-Y',$ngay). ' + 1 days')));

            $data_q = array(
                'where' => 'tire_brand ='.$order->tire_brand.' AND tire_size ='.$order->tire_size.' AND tire_pattern ='.$order->tire_pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                'order_by' => 'start_date',
                'order' => 'DESC',
                'limit' => 1,
            );
            $tire_price_discounts = $tire_price_discount_model->getAllTire($data_q);

            $data_e = array(
                'where' => 'tire_brand ='.$order->tire_brand.' AND tire_size ='.$order->tire_size.' AND tire_pattern ='.$order->tire_pattern.' AND start_date < '.$ngaysau.' AND (end_date IS NULL OR end_date > '.$ngaytruoc.')',
                'order_by' => 'start_date',
                'order' => 'DESC',
                'limit' => 1,
            );

            $tire_price_discount_events = $tire_price_discount_event_model->getAllTire($data_e);

            if ($tire_price_discounts) {
                foreach ($tire_price_discounts as $pr) {
                    $price[$order->order_tire_list_id] = $pr->tire_price;
                    foreach ($tire_price_discount_events as $event) {
                        if ($event->percent_discount > 0) {
                            $price[$order->order_tire_list_id] = $pr->tire_price*((100-$event->percent_discount)/100);
                        }
                        else{
                            $price[$order->order_tire_list_id] = $pr->tire_price-$event->money_discount;
                        }
                    }
                }
            }
            else{
                $tire_brand = $tire_brand_model->getTire($order->tire_brand);
                if ($tire_brand->tire_brand_name == "Aoteli" || $tire_brand->tire_brand_name == "Yatai" || $tire_brand->tire_brand_name == "Yatone" || $tire_brand->tire_brand_name == "Three-A") {
                    $tire_brand_name = "Shengtai";
                }
                else if ($tire_brand->tire_brand_name == "Guangda" || $tire_brand->tire_brand_name == "Qiangwei") {
                            $tire_brand_name = "Amberstone";
                        }
                else{
                    $tire_brand_name = $tire_brand->tire_brand_name;
                }

                $tire_size_number = $tire_size_model->getTire($order->tire_size)->tire_size_number;
                $pattern_type = $tire_pattern_model->getTire($order->tire_pattern)->tire_pattern_type;

                $brand = $tire_quotation_brand_model->getTireByWhere(array('tire_quotation_brand_name'=>$tire_brand_name));
                $brand = $brand?$brand->tire_quotation_brand_id:null;
                $size = $tire_quotation_size_model->getTireByWhere(array('tire_quotation_size_number'=>$tire_size_number));
                $size = $size?$size->tire_quotation_size_id:null;

                if ($order_tire->delivery_date > 0) {
                    $data_q = array(
                        'where' => 'tire_quotation_brand ='.$brand.' AND tire_quotation_size ='.$size.' AND tire_quotation_pattern IN ('.$pattern_type.') AND start_date <= '.$order_tire->delivery_date.' AND (end_date IS NULL OR end_date >= '.$order_tire->delivery_date.')',
                    );
                }
                else{
                    $data_q = array(
                        'where' => 'tire_quotation_brand ='.$brand.' AND tire_quotation_size ='.$size.' AND tire_quotation_pattern IN ('.$pattern_type.') AND start_date <= '.$order_tire->order_tire_date.' AND (end_date IS NULL OR end_date >= '.$order_tire->order_tire_date.')',
                    );
                }
                
                $prices = $tire_quotation_model->getAllTire($data_q);
                foreach ($prices as $p) {
                    $price[$order->order_tire_list_id] = $p->tire_quotation_price;
                }
            }

            

            
        }

        

        $this->view->data['price'] = $price;
        $this->view->data['order'] = $id;
        $this->view->data['order_tire'] = $order_tire;

        $this->view->show('ordertire/listtire');
    }
    public function ordercost($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;

        $order_tire_model = $this->model->get('ordertireModel');
        $order_tire = $order_tire_model->getTire($id);
        
        $user_model = $this->model->get('userModel');
        $order_tire_cost_model = $this->model->get('ordertirecostModel');
        $join = array('table'=>'shipment_vendor','where'=>'vendor=shipment_vendor_id');

        $data = array(
            'where' => 'order_tire='.$id,
        );

        $order_tire_costs = $order_tire_cost_model->getAllTire($data,$join);

        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data[$user->user_id]['username'] = $user->username;
        }

        $payable_model = $this->model->get('payableModel');

        $pay_data = array();
        foreach ($order_tire_costs as $order) {
            $payables = $payable_model->getCostsByWhere(array('order_tire'=>$order->order_tire,'vendor'=>$order->vendor,'cost_type'=>$order->order_tire_cost_type));
            $pay_data[$order->order_tire_cost_id]['money'] = $payables->pay_money;
            $pay_data[$order->order_tire_cost_id]['date'] = $payables->pay_date;
            $pay_data[$order->order_tire_cost_id]['user'] = $order->order_tire_cost_create_user>0?$user_data[$order->order_tire_cost_create_user]['username']:null;
        }
        $this->view->data['pay_data'] = $pay_data;

        $this->view->data['order_tire_costs'] = $order_tire_costs;

        $this->view->data['order'] = $id;

        $this->view->data['order_tire'] = $order_tire;

        $this->view->show('ordertire/ordercost');
    }
    public function listcost($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $user_model = $this->model->get('userModel');
        $order_tire_cost_model = $this->model->get('ordertirecostModel');
        $join = array('table'=>'shipment_vendor','where'=>'vendor=shipment_vendor_id');

        $data = array(
            'where' => 'order_tire='.$id,
        );

        $order_tire_costs = $order_tire_cost_model->getAllTire($data,$join);

        $users = $user_model->getAllUser();
        $user_data = array();
        foreach ($users as $user) {
            $user_data[$user->user_id]['username'] = $user->username;
        }

        $payable_model = $this->model->get('payableModel');

        $pay_data = array();
        foreach ($order_tire_costs as $order) {
            $payables = $payable_model->getCostsByWhere(array('order_tire'=>$order->order_tire,'vendor'=>$order->vendor,'cost_type'=>$order->order_tire_cost_type));
            $pay_data[$order->order_tire_cost_id]['money'] = $payables->pay_money;
            $pay_data[$order->order_tire_cost_id]['date'] = $payables->pay_date;
            $pay_data[$order->order_tire_cost_id]['user'] = $order->order_tire_cost_create_user>0?$user_data[$order->order_tire_cost_create_user]['username']:null;
        }
        $this->view->data['pay_data'] = $pay_data;

        $this->view->data['order_tire_costs'] = $order_tire_costs;

        $this->view->show('ordertire/listcost');
    }
    public function listdiscount($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tires = $order_tire_model->getTire($id);
        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['lock'] = $this->registry->router->page;

        $this->view->show('ordertire/listdiscount');
    }
    public function discountedit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['order_tire']);
            $discount = trim(str_replace(',','',$_POST['discount']));
            $reduce = trim(str_replace(',','',$_POST['reduce']));
            $ck_ttn = trim($_POST['ck_ttn']);
            $ck_kho = trim($_POST['ck_kho']);
            $ck_sl = trim($_POST['ck_sl']);

            $order = $order_tire_model->getTire($id);
            $total = $order->total+$order->discount+$order->reduce-$discount-$reduce;

            $data = array(
                'discount'=>$discount,
                'reduce'=>$reduce,
                'ck_ttn'=>$ck_ttn,
                'ck_kho'=>$ck_kho,
                'ck_sl'=>$ck_sl,
                'total'=>$total,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $receivable_data = array(
                'money' => $total,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $obtain_data = array(
                'money' => $total,
            );

            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$id,'money'=>$order->total));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|discount|".$_POST['order_tire']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function listwarranty($id){
        $this->view->disableLayout();
        $this->view->data['lib'] = $this->lib;
        $order_tire_model = $this->model->get('ordertireModel');
        $order_tires = $order_tire_model->getTire($id);
        $this->view->data['order_tires'] = $order_tires;
        $this->view->data['lock'] = $this->registry->router->page;

        $this->view->show('ordertire/listwarranty');
    }
    public function editwarranty(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['data']);
            $warranty = trim(str_replace(',','',$_POST['warranty']));
            $warranty_percent = trim($_POST['warranty_percent']);

            $order = $order_tire_model->getTire($id);
            $total = $order->total+$order->warranty-$warranty;

            $data = array(
                'warranty'=>$warranty,
                'warranty_percent'=>$warranty_percent,
                'total'=>$total,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $receivable_data = array(
                'money' => $total,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $obtain_data = array(
                'money' => $total,
            );

            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$id,'money'=>$order->total));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|warranty|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function editorder(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            

            $brand = trim($_POST['tire_brand']);
            $pattern = trim($_POST['tire_pattern']);
            $size = trim($_POST['tire_size']);
            $number = trim($_POST['tire_number']);
            $price = trim(str_replace(',','',$_POST['tire_price_vat']));
            $check_price_vat = trim($_POST['check_price_vat']);
            $price_vat = trim(str_replace(',','',$_POST['tire_price']));
            $tire_date = trim($_POST['tire_date']);

            $data = array(
                'tire_brand'=>$brand,
                'tire_pattern'=>$pattern,
                'tire_size'=>$size,
                'tire_number'=>$number,
                'tire_price'=>$price,
                'tire_price_vat'=>$price_vat,
                'tire_date'=>$tire_date,
            );

            if ($_POST['yes'] != "") {
                if (!$order_tire_list_model->getAllTireByWhere($_POST['yes'].' AND order_tire='.$_POST['order'].' AND tire_brand='.$brand.' AND tire_size='.$size.' AND tire_pattern='.$pattern)) {
                    $order_tire_list = $order_tire_list_model->getTire($_POST['yes']);

                    $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                    $order_tire_list_model->updateTire($data,array('order_tire_list_id'=>$_POST['yes']));

                    $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                    $total_number = 0;
                    $total = 0;
                    $vat = 0;
                    foreach ($order_lists as $od) {
                        $total_number += $od->tire_number;
                        
                        if ($order_tire->check_price_vat == 1) {
                            $p = $od->tire_price_vat;
                            $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                            $n = $p-$v;

                            $vat += $v*$od->tire_number;
                            $total += $od->tire_number*$od->tire_price_vat;
                        }
                        else{
                            $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                        }
                    }

                    $discount = $order_tire->discount+$order_tire->reduce;
                    $warranty = round($total*$order_tire->warranty_percent/100);
                    $total = $total - $discount - $warranty;


                    $data_order = array(
                        'total'=>$total,
                        'order_tire_number'=>$total_number,
                        'vat'=> $vat,
                        'warranty'=>$warranty,
                    );


                    $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                    if($order_tire->order_tire_status==1){
                        $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                        $order_tire_list_old = $order_tire_list_model->getTire($_POST['yes']);

                        $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                        $tire_sale = $tire_sale_model->getTireByWhere(array('tire_brand'=>$order_tire_list->tire_brand,'tire_size'=>$order_tire_list->tire_size,'tire_pattern'=>$order_tire_list->tire_pattern,'order_tire'=>$order_tire_list->order_tire));
                        $data_sale = array(
                            'tire_brand'=>$order_tire_list_old->tire_brand,
                            'tire_size'=>$order_tire_list_old->tire_size,
                            'tire_pattern'=>$order_tire_list_old->tire_pattern,
                            'volume' => $order_tire_list_old->tire_number,
                            'sell_price' => $order_tire_list_old->tire_price,
                            'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                            'date_manufacture_sale' => $order_tire_list_old->tire_date,
                        );
                        $tire_sale_model->updateTire($data_sale,array('tire_sale_id'=>$tire_sale->tire_sale_id));

                        $obtain_data = array(
                            'obtain_date' => $order_tire_old->delivery_date,
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'order_tire' => $order_tire_list->order_tire,
                        );
                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }
                        $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                        $receivable_data = array(
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'receivable_date' => $order_tire_old->delivery_date,
                            'expect_date' => $order_tire_old->delivery_date,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'code' => $order_tire_old->order_number,
                            'source' => 1,
                            'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'order_tire' => $order_tire_list->order_tire,
                            'check_vat' => $order_tire_old->vat>0?1:0,
                        );

                        
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }

                        $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                    }

                    echo "Cập nhật thành công";

                                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                                $filename = "action_logs.txt";
                                $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                                
                                $fh = fopen($filename, "a") or die("Could not open log file.");
                                fwrite($fh, $text) or die("Could not write file!");
                                fclose($fh);
                }
                else{
                    echo "Mã hàng đã tồn tại";
                }
                
            }
            else{
                $data['order_tire'] = $_POST['order'];

                if (!$order_tire_list_model->getTireByWhere(array('order_tire'=>$data['order_tire'], 'tire_brand'=>$brand, 'tire_size'=>$size, 'tire_pattern'=>$pattern))) {
                    $order_tire_list_model->createTire($data);

                    $order_tire_list = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                    $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                    $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                    $total_number = 0;
                    $total = 0;
                    $vat = 0;
                    foreach ($order_lists as $od) {
                        $total_number += $od->tire_number;
                        
                        if ($order_tire->check_price_vat == 1) {
                            $p = $od->tire_price_vat;
                            $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                            $n = $p-$v;

                            $vat += $v*$od->tire_number;
                            $total += $od->tire_number*$od->tire_price_vat;
                        }
                        else{
                            $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                        }
                    }

                    $discount = $order_tire->discount+$order_tire->reduce;
                    $warranty = round($total*$order_tire->warranty_percent/100);
                    $total = $total - $discount - $warranty;

                    $data_order = array(
                        'total'=>$total,
                        'order_tire_number'=>$total_number,
                        'vat'=> $vat,
                        'warranty'=>$warranty,
                    );


                    $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                    if($order_tire->order_tire_status==1){

                        $order_tire_model->updateTire(array('sale_lock'=>1),array('order_tire_id'=>$order_tire_list->order_tire));

                        $order_tire_list_old = $order_tire_list_model->getTire($order_tire_list_model->getLastTire()->order_tire_list_id);

                        $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                        $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire_old->sale));

                        $check_vat = $order_tire_old->vat>0?1:0;
                        //$vat = $order->tire_price*$order_tire->vat_percent/100;
                        $data_sale = array(
                                
                            'code' => $order_tire_old->order_number,
                            'volume' => $order_tire_list_old->tire_number,
                            'tire_brand' => $order_tire_list_old->tire_brand,
                            'tire_size' => $order_tire_list_old->tire_size,
                            'sell_price' => $order_tire_list_old->tire_price,
                            'sell_price_vat' => $order_tire_list_old->tire_price_vat,
                            'customer' => $order_tire_old->customer,
                            'tire_sale_date' => $order_tire_old->delivery_date,
                            //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                            'tire_pattern' => $order_tire_list_old->tire_pattern,
                            'check_vat' => $check_vat,
                            'sale' => $staff->staff_id,
                            'customer_type' => $order_tire_old->customer_type,
                            'order_tire' => $order_tire_list->order_tire,
                            'date_manufacture_sale' => $order_tire_list_old->tire_date,
                        );
                        $tire_sale_model->createTire($data_sale);

                        $obtain_data = array(
                            'obtain_date' => $order_tire_old->delivery_date,
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'order_tire' => $order_tire_list->order_tire,
                        );
                        if($obtain_data['week'] == 53){
                            $obtain_data['week'] = 1;
                            $obtain_data['year'] = $obtain_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }
                        $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                        $receivable_data = array(
                            'customer' => $order_tire_old->customer,
                            'money' => $order_tire_old->total,
                            'receivable_date' => $order_tire_old->delivery_date,
                            'expect_date' => $order_tire_old->delivery_date,
                            'week' => (int)date('W',$order_tire_old->delivery_date),
                            'year' => (int)date('Y',$order_tire_old->delivery_date),
                            'code' => $order_tire_old->order_number,
                            'source' => 1,
                            'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                            'create_user' => $_SESSION['userid_logined'],
                            'type' => 4,
                            'order_tire' => $order_tire_list->order_tire,
                            'check_vat' => $order_tire_old->vat>0?1:0,
                        );

                        
                        if($receivable_data['week'] == 53){
                            $receivable_data['week'] = 1;
                            $receivable_data['year'] = $receivable_data['year']+1;
                        }
                        if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                            $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                        }

                        $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                    }

                    echo "Thêm thành công";

                                date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                                $filename = "action_logs.txt";
                                $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".implode("-",$data)."|order_tire_list|"."\n"."\r\n";
                                
                                $fh = fopen($filename, "a") or die("Could not open log file.");
                                fwrite($fh, $text) or die("Could not write file!");
                                fclose($fh);
                }
                else{
                    echo "Mã hàng đã tồn tại";
                }

                
            }

            $order_tire = $order_tire_model->getTire($_POST['order']);

            if ($order_tire->id_order_agent>0) {
                $tire_pattern_model = $this->model->get('tirepatternModel');
                $tire_brand_model = $this->model->get('tirebrandModel');
                $tire_size_model = $this->model->get('tiresizeModel');
                $customer_model = $this->model->get('customerModel');

                $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire->order_tire_id));
                $order_agent = array();
                $i=0;
                foreach ($order_lists as $order_list) {
                    $order_agent[$i]['tire_brand'] = $tire_brand_model->getTire($order_list->tire_brand)->tire_brand_name;
                    $order_agent[$i]['tire_size'] = $tire_size_model->getTire($order_list->tire_size)->tire_size_number;
                    $order_agent[$i]['tire_pattern'] = $tire_pattern_model->getTire($order_list->tire_pattern)->tire_pattern_name;
                    $order_agent[$i]['tire_number'] = $order_list->tire_number;
                    $order_agent[$i]['tire_price'] = $order_list->tire_price_vat;

                    $i++;
                }

                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/editagentorder';

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                   'order_tire' => $order_agent,
                   'total_number'=>$order_tire->order_tire_number,
                   'total'=>$order_tire->total,
                   'order_number'=>$order_tire->order_number,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $result = curl_exec($ch);

                // close connection
                curl_close($ch);
            }
            

        }
    }

    public function editvat(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $id = trim($_POST['data']);
            $vat_percent = trim(str_replace(',','',$_POST['vat_percent']));
            $vat = trim(str_replace(',','',$_POST['vat']));
            $thu = trim(str_replace(',','',$_POST['thu']));
            $check_vat = trim($_POST['check_vat']);

            $order_tire_model->updateTire(array('check_price_vat'=>$check_vat),array('order_tire_id'=>$id));

            $order = $order_tire_model->getTire($id);

            $total = $order->total;
            if ($order->check_price_vat!=1) {
                $total = $order->total-$order->vat+$vat;
            }
            

            $data = array(
                'vat'=>$vat,
                'vat_percent'=>$vat_percent,
                'total'=>$total,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            
            $receivable_data = array(
                'money' => $total,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $obtain_data = array(
                'money' => $total,
            );

            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$id,'money'=>$order->total));

            if ($order->check_price_vat==1) {
                $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$id));
                foreach ($order_lists as $ord) {
                    $tv = round($ord->tire_price_vat*$vat_percent/110);
                    $dg = $ord->tire_price_vat-$tv;

                    $tire_sale = $tire_sale_model->getTireByWhere(array('tire_brand'=>$ord->tire_brand,'tire_size'=>$ord->tire_size,'tire_pattern'=>$ord->tire_pattern,'order_tire'=>$id));
                    $tire_sale_model->updateTire(array('sell_price'=>$dg),array('tire_sale_id'=>$tire_sale->tire_sale_id));

                    $order_tire_list_model->updateTire(array('tire_price'=>$dg),array('order_tire_list_id'=>$ord->order_tire_list_id));
                }
            }

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|vat|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }

    public function changedue(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            
            $id = trim($_POST['data']);

            $data = array(
                'due_date'=>strtotime($_POST['due_date']),
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|due_date|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function changedelivery(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            
            $id = trim($_POST['data']);

            $data = array(
                'delivery_date'=>strtotime($_POST['de_date']),
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|delivery_date|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function changestaff(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            
            $id = trim($_POST['data']);
            $order_staff = strtolower(trim($_POST['order_staff']));

            $data = array(
                'sale'=>$order_staff,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $staff_model = $this->model->get('staffModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $check_sale_salary_model = $this->model->get('checksalesalaryModel');

            $staffs = $staff_model->getStaffByWhere(array('account'=>$order_staff));
            
            $data_sale = array(
                'sale' => $staffs->staff_id,
            );
            $tire_sale_model->updateTire($data_sale,array('order_tire'=>$id));

            $data_salary = array(
                'staff' => $staffs->staff_id,
            );
            $check_sale_salary_model->updateSalary($data_salary,array('order_tire'=>$id));
            
            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|staff|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function changecustomer(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            
            $id = trim($_POST['data']);
            $order_customer = strtolower(trim($_POST['order_customer']));

            $data = array(
                'customer'=>$order_customer,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');

            $data_sale = array(
                'customer' => $order_customer,
            );
            $tire_sale_model->updateTire($data_sale,array('order_tire'=>$id));

            $data_obtain = array(
                'customer' => $order_customer,
            );
            $obtain_model->updateObtain($data_obtain,array('order_tire'=>$id));

            $data_receivable = array(
                'customer' => $order_customer,
            );
            $receivable_model->updateCosts($data_receivable,array('order_tire'=>$id));
            
            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|customer|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function addordernumber(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['data']);
            $order_number = strtolower(trim($_POST['order_number']));

            $data = array(
                'order_number'=>$order_number,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $receivable_model = $this->model->get('receivableModel');
            $payable_model = $this->model->get('payableModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            
            $data_sale = array(
                'code' => $order_number,
            );
            $tire_sale_model->updateTire($data_sale,array('order_tire'=>$id));
            
            $receivable_data = array(
                'code' => $order_number,
            );

            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$id));

            $payable_data = array(
                'code' => $order_number,
            );

            $payable_model->updateCosts($payable_data,array('order_tire'=>$id));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|code|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function addcomment(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');

            $id = trim($_POST['data']);
            $order_comment = trim($_POST['order_comment']);

            $data = array(
                'order_tire_comment'=>$order_comment,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));


            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|comment|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function approve(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $customer_model = $this->model->get('customerModel');

            $id = trim($_POST['data']);

            $order_tire = $order_tire_model->getTire($id);

            $data = array(
                'approve'=>$_SESSION['userid_logined'],
                'sale_lock'=>1,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            if ($order_tire->id_order_agent>0) {
                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/approveorder';

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $result = curl_exec($ch);

                // close connection
                curl_close($ch);
            }
            

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|approve|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function approveremove(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $customer_model = $this->model->get('customerModel');

            $id = trim($_POST['data']);

            $order_tire = $order_tire_model->getTire($id);

            $data = array(
                'approve'=>null,
                'sale_lock'=>0,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            if ($order_tire->id_order_agent>0) {
                $data = array(
                    'approve'=>null,
                    'sale_lock'=>1,
                );

                $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/approveorderremove';

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $result = curl_exec($ch);

                // close connection
                curl_close($ch);
            }
            

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|approve|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }
    public function revert(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            //$owe_model = $this->model->get('oweModel');
            //$payable_model = $this->model->get('payableModel');
            $obtain_model = $this->model->get('obtainModel');
            $receivable_model = $this->model->get('receivableModel');
            $assets = $this->model->get('assetsModel');
            $receive = $this->model->get('receiveModel');
            //$pay = $this->model->get('payModel');
            $lift = $this->model->get('liftModel');
            $customer_model = $this->model->get('customerModel');

            $re = $receivable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
            foreach ($re as $r) {
                $assets->queryAssets('DELETE FROM assets WHERE receivable='.$r->receivable_id);
                $receive->queryCosts('DELETE FROM receive WHERE receivable='.$r->receivable_id);
            }
            // $pa = $payable_model->getAllCosts(array('where'=>'order_tire='.$_POST['data']));
            // foreach ($pa as $p) {
            //     $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
            //     $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
            // }

            $receivable_model->queryCosts('DELETE FROM receivable WHERE order_tire = '.$_POST['data']);
            //$payable_model->queryCosts('DELETE FROM payable WHERE order_tire = '.$_POST['data']);
            $obtain_model->queryObtain('DELETE FROM obtain WHERE order_tire = '.$_POST['data']);
            //$owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$_POST['data']);
            $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE order_tire = '.$_POST['data']);
            $lift->queryLift('DELETE FROM lift WHERE order_tire = '.$_POST['data']);

            $data = array(
                'order_tire_status'=>null,
                'delivery_date'=>null,
                'sale_lock'=>0,
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$_POST['data']));

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|revert|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

            $order_tire = $order_tire_model->getTire($_POST['data']);

            if ($order_tire->id_order_agent>0) {
                $customers = $customer_model->getCustomer($order_tire->customer);
                // where are we posting to?
                $url = $customers->customer_agent_link.'/ordertire/revertorder';

                // what post fields?
                $fields = array(
                   'id_order_tire' => $order_tire->id_order_agent,
                );
                // build the urlencoded data
                $postvars = http_build_query($fields);

                // open connection
                $ch = curl_init();

                // set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                // execute post
                $result = curl_exec($ch);

                // close connection
                curl_close($ch);
            }
        }
    }
    public function exstock(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $customer_model = $this->model->get('customerModel');

            $id = trim($_POST['data']);

            $order_tire = $order_tire_model->getTire($id);

            if ($order_tire->order_tire_status != 1) {
                $data = array(
                    'order_tire_status'=>1,
                    'delivery_date'=>strtotime($_POST['delivery_date']),
                    'arrival_date'=>strtotime($_POST['arrival_date']),
                    'sale_lock'=>1,
                );

                $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

                $week = (int)date('W',$data['delivery_date']);
                $year = (int)date('Y',$data['delivery_date']);

                if($week == 53){
                    $week = 1;
                    $year = $year+1;
                }
                if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                    $year = (int)date('Y',$data['delivery_date'])+1;
                }

                $owe_model->updateOwe(array('owe_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
                $payable_model->updateCosts(array('payable_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));

                
                $order_tire_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire = '.$id));
                $staff = $staff_model->getStaffByWhere(array('account'=>$order_tire->sale));

                foreach ($order_tire_lists as $order) {
                    $check_vat = $order_tire->vat>0?1:0;
                    //$vat = $order->tire_price*$order_tire->vat_percent/100;
                    $data_sale = array(
                            
                        'code' => $order_tire->order_number,
                        'volume' => $order->tire_number,
                        'tire_brand' => $order->tire_brand,
                        'tire_size' => $order->tire_size,
                        'sell_price' => $order->tire_price,
                        'sell_price_vat' => $order->tire_price_vat,
                        'customer' => $order_tire->customer,
                        'tire_sale_date' => $data['delivery_date'],
                        //'tire_sale_date_expect' => strtotime($_POST['tire_sale_date_expect']),
                        'tire_pattern' => $order->tire_pattern,
                        'check_vat' => $check_vat,
                        'sale' => $staff->staff_id,
                        'customer_type' => $order_tire->customer_type,
                        'order_tire' => $id,
                        'date_manufacture_sale' => $order->tire_date,
                    );
                    $tire_sale_model->createTire($data_sale);
                }

                $obtain_data = array(
                    'obtain_date' => $data['delivery_date'],
                    'customer' => $order_tire->customer,
                    'money' => $order_tire->total,
                    'week' => (int)date('W',$data['delivery_date']),
                    'year' => (int)date('Y',$data['delivery_date']),
                    'order_tire' => $id,
                );
                if($obtain_data['week'] == 53){
                    $obtain_data['week'] = 1;
                    $obtain_data['year'] = $obtain_data['year']+1;
                }
                if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                    $obtain_data['year'] = (int)date('Y',$data['delivery_date'])+1;
                }
                $obtain_model->createObtain($obtain_data);

                $receivable_data = array(
                    'customer' => $order_tire->customer,
                    'money' => $order_tire->total,
                    'receivable_date' => $data['delivery_date'],
                    'expect_date' => $data['delivery_date'],
                    'week' => (int)date('W',$data['delivery_date']),
                    'year' => (int)date('Y',$data['delivery_date']),
                    'code' => $order_tire->order_number,
                    'source' => 1,
                    'comment' => $order_tire->order_tire_number.' lốp '.$order_tire->order_number,
                    'create_user' => $_SESSION['userid_logined'],
                    'type' => 4,
                    'order_tire' => $id,
                    'check_vat' => $order_tire->vat>0?1:0,
                );

                
                if($receivable_data['week'] == 53){
                    $receivable_data['week'] = 1;
                    $receivable_data['year'] = $receivable_data['year']+1;
                }
                if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                    $receivable_data['year'] = (int)date('Y',$data['delivery_date'])+1;
                }

                $receivable_model->createCosts($receivable_data);


                $lift_model = $this->model->get('liftModel');

                $contributor = "";
                if(trim($_POST['lift']) != ""){
                    $support = explode(',', trim($_POST['lift']));

                    if ($support) {
                        foreach ($support as $key) {
                            $name = $staff_model->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                            if ($contributor == "")
                                $contributor .= $name;
                            else
                                $contributor .= ','.$name;
                        }
                    }

                    if ($contributor != "") {
                        $data_lift = array(
                            'lift_date' => $data['delivery_date'],
                            'staff' => $contributor,
                            'order_tire' => $id,
                        );
                        $lift_model->createLift($data_lift);
                    }
                    
                }

                echo "Cập nhật thành công";

                            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                            $filename = "action_logs.txt";
                            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|exstock|".$_POST['data']."|order_tire|"."\n"."\r\n";
                            
                            $fh = fopen($filename, "a") or die("Could not open log file.");
                            fwrite($fh, $text) or die("Could not write file!");
                            fclose($fh);

                if ($order_tire->id_order_agent>0) {
                    $customers = $customer_model->getCustomer($order_tire->customer);
                    // where are we posting to?
                    $url = $customers->customer_agent_link.'/ordertire/receiveorder';

                    // what post fields?
                    $fields = array(
                       'id_order_tire' => $order_tire->id_order_agent,
                       'delivery_date' => $data['delivery_date'],
                       'order_number' => $order_tire->order_number,
                    );
                    // build the urlencoded data
                    $postvars = http_build_query($fields);

                    // open connection
                    $ch = curl_init();

                    // set the url, number of POST vars, POST data
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, count($fields));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                    // execute post
                    $result = curl_exec($ch);

                    // close connection
                    curl_close($ch);
                }

            }
            

        }
    }
    public function exstockedit(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $staff_model = $this->model->get('staffModel');
            $owe_model = $this->model->get('oweModel');
            $payable_model = $this->model->get('payableModel');
            $lift_model = $this->model->get('liftModel');

            $id = trim($_POST['data']);

            $data = array(
                'order_tire_status'=>1,
                'delivery_date'=>strtotime($_POST['delivery_date']),
                'arrival_date'=>strtotime($_POST['arrival_date']),
            );

            $order_tire_model->updateTire($data,array('order_tire_id'=>$id));

            $week = (int)date('W',$data['delivery_date']);
            $year = (int)date('Y',$data['delivery_date']);

            if($week == 53){
                $week = 1;
                $year = $year+1;
            }
            if (((int)date('W',$data['delivery_date']) == 1) && ((int)date('m',$data['delivery_date']) == 12) ) {
                $year = (int)date('Y',$data['delivery_date'])+1;
            }

            $owe_model->updateOwe(array('owe_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $payable_model->updateCosts(array('payable_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $tire_sale_model->updateTire(array('tire_sale_date' => $data['delivery_date']),array('order_tire'=>$id));
            $obtain_model->updateObtain(array('obtain_date'=>$data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));
            $receivable_model->updateCosts(array('receivable_date'=>$data['delivery_date'],'expect_date' => $data['delivery_date'],'week'=>$week,'year'=>$year),array('order_tire'=>$id));



            $contributor = "";
            if(trim($_POST['lift']) != ""){
                $support = explode(',', trim($_POST['lift']));

                if ($support) {
                    foreach ($support as $key) {
                        $name = $staff_model->getStaffByWhere(array('staff_name'=>trim($key)))->staff_id;
                        if ($contributor == "")
                            $contributor .= $name;
                        else
                            $contributor .= ','.$name;
                    }
                }

                $data_lift = array(
                    'lift_date' => $data['delivery_date'],
                    'staff' => $contributor,
                    'order_tire' => $id,
                );

                if ($lift_model->getLiftByWhere(array('order_tire'=>$id))) {
                    $lift_model->updateLift($data_lift,array('order_tire'=>$id));
                }
                else{
                    $lift_model->createLift($data_lift);
                }
                
            }
            else{
                $lift_model->queryLift('DELETE FROM lift WHERE order_tire = '.$id);
            }
            

            echo "Cập nhật thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|exstock|".$_POST['data']."|order_tire|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);

        }
    }

    public function deleteorder(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_tire_list_model = $this->model->get('ordertirelistModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $receivable_model = $this->model->get('receivableModel');
            $obtain_model = $this->model->get('obtainModel');
            $tire_sale_model = $this->model->get('tiresaleModel');
            $customer_model = $this->model->get('customerModel');
            $tire_brand_model = $this->model->get('tirebrandModel');
            $tire_pattern_model = $this->model->get('tiresizeModel');
            $tire_size_model = $this->model->get('tirepatternModel');
            if(isset($_POST['data'])){
                        

                        $order_tire_list = $order_tire_list_model->getTire($_POST['data']);

                        $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                        

                        // $total_number = $order_tire->order_tire_number;
                        // $total = $order_tire->total;
                        // $total_after = $order_tire_list->tire_number*$order_tire_list->tire_price;
                        // $vat = $order_tire->vat;
                        // $discount = $order_tire->discount;

                        // $total_number = $total_number - $order_tire_list->tire_number;
                        // $total = $total - $total_after;

                        // if ($order_tire->ck_ttn==1) {
                        //     $discount = $discount - ($total_after*0.02);
                        //     $total = $total + ($total_after*0.02);
                        // }
                        // if ($order_tire->ck_kho==1) {
                        //     $discount = $discount - ($order_tire_list->tire_number*100000);
                        //     $total = $total + ($order_tire_list->tire_number*100000);
                        // }
                        // if ($order_tire->ck_sl==1) {
                        //     if ($order_tire->order_tire_number >= 20 && $order_tire->order_tire_number < 50) {
                        //         $discount = $discount - ($total_after*0.01);
                        //         $total = $total + ($total_after*0.01);
                        //     }
                        //     else if ($order_tire->order_tire_number >= 50 && $order_tire->order_tire_number < 100) {
                        //         $discount = $discount - ($total_after*0.02);
                        //         $total = $total + ($total_after*0.02);
                        //     }
                        //     else if ($order_tire->order_tire_number >= 100) {
                        //         $discount = $discount - ($total_after*0.03);
                        //         $total = $total + ($total_after*0.03);
                        //     }
                        // }

                        // if ($order_tire->vat_percent > 0) {
                        //     $vat = $vat - ($total_after*$order_tire->vat_percent/100);
                        //     $total = $total - ($total_after*$order_tire->vat_percent/100);
                        // }

                        

                        // $data_order = array(
                        //     'discount'=>$discount,
                        //     'total'=>$total,
                        //     'order_tire_number'=>$total_number,
                        //     'vat'=> $vat,
                        // );

                        $order_tire_list_model->deleteTire($_POST['data']);

                        $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$order_tire_list->order_tire));
                        $total_number = 0;
                        $total = 0;
                        $vat = 0;
                        foreach ($order_lists as $od) {
                            $total_number += $od->tire_number;
                            
                            if ($order_tire->check_price_vat == 1) {
                                $p = $od->tire_price_vat;
                                $v = round(($p*$order_tire->vat_percent*0.1)/1.1*0.1);
                                $n = $p-$v;

                                $vat += $v*$od->tire_number;
                                $total += $od->tire_number*$od->tire_price_vat;
                            }
                            else{
                                $vat += round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                                $total += $od->tire_number*$od->tire_price+round($od->tire_number*$od->tire_price*$order_tire->vat_percent/100);
                            }
                        }

                        $discount = $order_tire->discount+$order_tire->reduce;
                        $warranty = round($total*$order_tire->warranty_percent/100);
                        $total = $total - $discount - $warranty;


                        $data_order = array(
                            'total'=>$total,
                            'order_tire_number'=>$total_number,
                            'vat'=> $vat,
                            'warranty'=>$warranty,
                        );


                        $order_tire_model->updateTire($data_order,array('order_tire_id'=>$order_tire_list->order_tire));

                        if($order_tire->order_tire_status==1){

                            $order_tire_list_old = $order_tire_list_model->getTire($_POST['data']);

                            $order_tire_old = $order_tire_model->getTire($order_tire_list->order_tire);

                            $tire_sale_model->queryTire('DELETE FROM tire_sale WHERE tire_brand = '.$order_tire_list_old->tire_brand.' AND tire_size = '.$order_tire_list_old->tire_size.' AND tire_pattern = '.$order_tire_list_old->tire_pattern.' AND order_tire = '.$order_tire_list->order_tire);

                            $obtain_data = array(
                                'obtain_date' => $order_tire_old->delivery_date,
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'order_tire' => $order_tire_list->order_tire,
                            );
                            if($obtain_data['week'] == 53){
                                $obtain_data['week'] = 1;
                                $obtain_data['year'] = $obtain_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $obtain_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }
                            $obtain_model->updateObtain($obtain_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));

                            $receivable_data = array(
                                'customer' => $order_tire_old->customer,
                                'money' => $order_tire_old->total,
                                'receivable_date' => $order_tire_old->delivery_date,
                                'expect_date' => $order_tire_old->delivery_date,
                                'week' => (int)date('W',$order_tire_old->delivery_date),
                                'year' => (int)date('Y',$order_tire_old->delivery_date),
                                'code' => $order_tire_old->order_number,
                                'source' => 1,
                                'comment' => $order_tire_old->order_tire_number.' lốp '.$order_tire_old->order_number,
                                'create_user' => $_SESSION['userid_logined'],
                                'type' => 4,
                                'order_tire' => $order_tire_list->order_tire,
                                'check_vat' => $order_tire_old->vat>0?1:0,
                            );

                            
                            if($receivable_data['week'] == 53){
                                $receivable_data['week'] = 1;
                                $receivable_data['year'] = $receivable_data['year']+1;
                            }
                            if (((int)date('W',$order_tire_old->delivery_date) == 1) && ((int)date('m',$order_tire_old->delivery_date) == 12) ) {
                                $receivable_data['year'] = (int)date('Y',$order_tire_old->delivery_date)+1;
                            }

                            $receivable_model->updateCosts($receivable_data,array('order_tire'=>$order_tire_list->order_tire,'customer'=>$order_tire_old->customer,'money'=>$order_tire->total));
                        }

                        if ($order_tire->id_order_agent>0) {
                            $order_tire = $order_tire_model->getTire($order_tire_list->order_tire);

                            $customers = $customer_model->getCustomer($order_tire->customer);
                            // where are we posting to?
                            $url = $customers->customer_agent_link.'/ordertire/deleteorderagentorder';

                            // what post fields?
                            $fields = array(
                                'tire_brand'=>$tire_brand_model->getTire($order_tire_list->tire_brand)->tire_brand_name,
                                'tire_pattern'=>$tire_pattern_model->getTire($order_tire_list->tire_pattern)->tire_pattern_name,
                                'tire_size'=>$tire_size_model->getTire($order_tire_list->tire_size)->tire_size_number,
                               'id_order_tire' => $order_tire->id_order_agent,
                               'total_number'=>$order_tire->order_tire_number,
                               'total'=>$order_tire->total,
                            );
                            // build the urlencoded data
                            $postvars = http_build_query($fields);

                            // open connection
                            $ch = curl_init();

                            // set the url, number of POST vars, POST data
                            curl_setopt($ch, CURLOPT_URL, $url);
                            curl_setopt($ch, CURLOPT_POST, count($fields));
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                            // execute post
                            $result = curl_exec($ch);

                            // close connection
                            curl_close($ch);

                        }

                        
                        echo "Xóa thành công";

                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|order_tire_list|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function editordercost(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');

            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');

            $order_tire_cost_type = trim($_POST['order_tire_cost_type']);
            $vendor = trim($_POST['vendor']);
            $comment = trim($_POST['comment']);
            $order_tire_cost_date = strtotime($_POST['order_tire_cost_date']);
            $order_tire_cost = str_replace(',', '', $_POST['order_tire_cost']);
            $order = trim($_POST['order']);

            if($vendor>0){
               $data_order = $order_tire_model->getTire($order);

                $cost = null;
                $cost_data = array(
                    'order_tire' => $order,
                    'order_tire_cost' => $order_tire_cost,
                    'order_tire_cost_date' => $order_tire_cost_date,
                    'vendor' => $vendor,
                    'comment' => $comment,
                    'order_tire_cost_type' => $order_tire_cost_type,
                    'order_tire_cost_create_user' => $_SESSION['userid_logined'],
                );
                $cost += $cost_data['order_tire_cost'];

                $owe_data = array(
                        'owe_date' => $data_order->delivery_date,
                        'vendor' => $cost_data['vendor'],
                        'money' => $cost_data['order_tire_cost'],
                        'week' => (int)date('W',$data_order->delivery_date),
                        'year' => (int)date('Y',$data_order->delivery_date),
                        'order_tire' => $order,
                    );
                    if($owe_data['week'] == 53){
                        $owe_data['week'] = 1;
                        $owe_data['year'] = $owe_data['year']+1;
                    }
                    if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                        $owe_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
                    }

                $payable_data = array(
                        'vendor' => $cost_data['vendor'],
                        'money' => $cost_data['order_tire_cost'],
                        'payable_date' => $data_order->delivery_date,
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $cost_data['order_tire_cost_date'],
                        'week' => (int)date('W',$data_order->delivery_date),
                        'year' => (int)date('Y',$data_order->delivery_date),
                        'code' => $data_order->order_number,
                        'source' => 1,
                        'comment' => $data_order->order_number.'-'.$cost_data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'order_tire' => $order,
                        'cost_type' => $cost_data['order_tire_cost_type'],
                        'approve' => null,
                        'check_cost'=>4,
                    );
                    if($payable_data['week'] == 53){
                        $payable_data['week'] = 1;
                        $payable_data['year'] = $payable_data['year']+1;
                    }
                    if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                        $payable_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
                    }


                if ($_POST['yes'] != "") {
                    $data_order_cost = $order_cost_model->getTire($_POST['yes']);
                    $order_cost_model->updateTire($cost_data,array('order_tire_cost_id'=>$data_order_cost->order_tire_cost_id));

                    $owe_model->updateOwe($owe_data,array('order_tire'=>$order,'vendor'=>$cost_data['vendor'],'money'=>$data_order_cost->order_tire_cost));
         
                    if($payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $data_order_cost->vendor,'order_tire'=>trim($order),'cost_type' => $data_order_cost->order_tire_cost_type))){
                        $check = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $data_order_cost->vendor,'order_tire'=>trim($order),'cost_type' => $data_order_cost->order_tire_cost_type));

                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                            $payable_data['approve'] = 10;
                        }

                        $payable_model->updateCosts($payable_data,array('payable_id'=>$check->payable_id));
                        
                    }

                    $order_tire_model->updateTire(array('order_cost'=>$cost+($data_order->order_cost-$data_order_cost->order_tire_cost)),array('order_tire_id'=>$order));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$data_order_cost->order_tire_cost_id."|order_tire_cost_|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                    echo "Cập nhật thành công";
                }
                else{
                    $order_cost_model->createTire($cost_data);

                    $owe_model->createOwe($owe_data);
                    $payable_model->createCosts($payable_data);

                    $last_cost = $order_cost_model->getLastTire()->order_tire_cost_id;

                    $order_tire_model->updateTire(array('order_cost'=>$cost+$data_order->order_cost),array('order_tire_id'=>$order));

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$last_cost."|order_tire_cost_|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                    echo "Thêm thành công";
                } 
            }
            else{
                echo "Vui lòng chọn tên Vendor";
            }
            

            
        }
    }

    public function addordercost(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');

            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');

            $data_order = $order_tire_model->getTire($_POST['data']);

            $vendor_cost = $_POST['vendor_cost'];

            $cost = null;
            foreach ($vendor_cost as $v) {
                $cost_data = array(
                    'order_tire' => $_POST['data'],
                    'order_tire_cost' => trim(str_replace(',','',$v['order_tire_cost'])),
                    'order_tire_cost_date' => strtotime(date('d-m-Y',strtotime($v['order_tire_cost_date']))),
                    'vendor' => $v['vendor'],
                    'comment' => trim($v['comment']),
                    'order_tire_cost_type' => $v['order_tire_cost_type'],
                    'order_tire_cost_create_user' => $_SESSION['userid_logined'],
                );
                $cost += $cost_data['order_tire_cost'];

                $owe_data = array(
                        'owe_date' => $data_order->delivery_date,
                        'vendor' => $cost_data['vendor'],
                        'money' => $cost_data['order_tire_cost'],
                        'week' => (int)date('W',$data_order->delivery_date),
                        'year' => (int)date('Y',$data_order->delivery_date),
                        'order_tire' => $_POST['data'],
                    );
                    if($owe_data['week'] == 53){
                        $owe_data['week'] = 1;
                        $owe_data['year'] = $owe_data['year']+1;
                    }
                    if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                        $owe_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
                    }

                $payable_data = array(
                        'vendor' => $cost_data['vendor'],
                        'money' => $cost_data['order_tire_cost'],
                        'payable_date' => $data_order->delivery_date,
                        'payable_create_date' => strtotime(date('d-m-Y H:i:s')),
                        'expect_date' => $cost_data['order_tire_cost_date'],
                        'week' => (int)date('W',$data_order->delivery_date),
                        'year' => (int)date('Y',$data_order->delivery_date),
                        'code' => $data_order->order_number,
                        'source' => 1,
                        'comment' => $data_order->order_number.'-'.$cost_data['comment'],
                        'create_user' => $_SESSION['userid_logined'],
                        'type' => 4,
                        'order_tire' => $_POST['data'],
                        'cost_type' => $cost_data['order_tire_cost_type'],
                        'approve' => null,
                        'check_cost'=>4,
                    );
                    if($payable_data['week'] == 53){
                        $payable_data['week'] = 1;
                        $payable_data['year'] = $payable_data['year']+1;
                    }
                    if (((int)date('W',$data_order->delivery_date) == 1) && ((int)date('m',$data_order->delivery_date) == 12) ) {
                        $payable_data['year'] = (int)date('Y',$data_order->delivery_date)+1;
                    }


                if ($order_cost_model->getTireByWhere(array('order_tire'=>$cost_data['order_tire'],'vendor'=>$cost_data['vendor'],'order_tire_cost_type'=>$cost_data['order_tire_cost_type']))) {
                    $data_order_cost = $order_cost_model->getTireByWhere(array('order_tire'=>$cost_data['order_tire'],'vendor'=>$cost_data['vendor'],'order_tire_cost_type'=>$cost_data['order_tire_cost_type']));
                    $order_cost_model->updateTire($cost_data,array('order_tire_cost_id'=>$data_order_cost->order_tire_cost_id));

                    $owe_model->updateOwe($owe_data,array('order_tire'=>$_POST['data'],'vendor'=>$cost_data['vendor'],'money'=>$data_order_cost->order_tire_cost));
         
                    if($payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $cost_data['vendor'],'order_tire'=>trim($_POST['data']),'cost_type' => $cost_data['order_tire_cost_type']))){
                        $check = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $cost_data['vendor'],'order_tire'=>trim($_POST['data']),'cost_type' => $cost_data['order_tire_cost_type']));

                        if ($check->money >= $payable_data['money'] && $check->approve > 0) {
                            $payable_data['approve'] = 10;
                        }

                        $payable_model->updateCosts($payable_data,array('check_cost'=>4,'money'=>$data_order_cost->order_tire_cost,'vendor' => $cost_data['vendor'],'order_tire'=>trim($_POST['data']),'cost_type' => $cost_data['order_tire_cost_type']));
                        
                    }

                    

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$data_order_cost->order_tire_cost_id."|order_tire_cost_|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);

                }
                else{
                    $order_cost_model->createTire($cost_data);

                    $owe_model->createOwe($owe_data);
                    $payable_model->createCosts($payable_data);

                    $last_cost = $order_cost_model->getLastTire()->order_tire_cost_id;

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                    $filename = "action_logs.txt";
                    $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$last_cost."|order_tire_cost_|"."\n"."\r\n";
                    
                    $fh = fopen($filename, "a") or die("Could not open log file.");
                    fwrite($fh, $text) or die("Could not write file!");
                    fclose($fh);
                }
            }

            $order_tire_model->updateTire(array('order_cost'=>$cost),array('order_tire_id'=>$_POST['data']));
        }
    }

    public function deleteordercost(){
        if(isset($_POST['data'])){
            $order_cost_model = $this->model->get('ordertirecostModel');
            $order_tire_model = $this->model->get('ordertireModel');
            $payable_model = $this->model->get('payableModel');
            $owe_model = $this->model->get('oweModel');
            $assets = $this->model->get('assetsModel');
            $pay = $this->model->get('payModel');

            $order_tire_cost = $order_cost_model->getTire($_POST['data']);
            $order_tire = $order_tire_model->getTire($order_tire_cost->order_tire);

            $p = $payable_model->getCostsByWhere(array('check_cost'=>4,'money'=>$order_tire_cost->order_tire_cost,'vendor'=>$order_tire_cost->vendor,'order_tire'=>$order_tire_cost->order_tire,'cost_type'=>$order_tire_cost->order_tire_cost_type));
            $owe_model->queryOwe('DELETE FROM owe WHERE order_tire = '.$order_tire_cost->order_tire.' AND vendor = '.$order_tire_cost->vendor.' AND money = '.$order_tire_cost->order_tire_cost);
            
            $assets->queryAssets('DELETE FROM assets WHERE payable='.$p->payable_id);
            $pay->queryCosts('DELETE FROM pay WHERE payable='.$p->payable_id);
            $payable_model->queryCosts('DELETE FROM payable WHERE payable_id='.$p->payable_id);

            $order_cost_model->queryTire('DELETE FROM order_tire_cost WHERE order_tire_cost_id = '.$order_tire_cost->order_tire_cost_id);

            $order_costs = $order_cost_model->getAllTire(array('where'=>'order_tire='.$order_tire->order_tire_id));
            $total=0;
            foreach ($order_costs as $order) {
                $total += $order->order_tire_cost;
            }

            $order_tire_model->updateTire(array('order_cost'=>$total),array('order_tire_id'=>$order_tire->order_tire_id));

            echo "Xóa thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$order_tire_cost->order_tire_cost_id."|order_tire_cost_|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }
    public function getvendor(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $vendor_model = $this->model->get('shipmentvendorModel');
            
            if ($_POST['keyword'] == "*") {
                $list = $vendor_model->getAllVendor();
            }
            else{
                $data = array(
                'where'=>'( shipment_vendor_name LIKE "%'.$_POST['keyword'].'%" )',
                );
                $list = $vendor_model->getAllVendor($data);
            }
            

            foreach ($list as $rs) {
                // put in bold the written text
                $shipment_vendor_name = $rs->shipment_vendor_name;
                if ($_POST['keyword'] != "*") {
                    $shipment_vendor_name = str_replace($_POST['keyword'], '<b>'.$_POST['keyword'].'</b>', $rs->shipment_vendor_name);
                }
                // add new option
                echo '<li onclick="set_item_other(\''.$rs->shipment_vendor_name.'\',\''.$rs->shipment_vendor_id.'\',\''.$_POST['offset'].'\')">'.$shipment_vendor_name.'</li>';
            }
        }
    }
    public function getordercost(){
        if(isset($_POST['order_tire'])){
            $payable_model = $this->model->get('payableModel');

            $order_cost_model = $this->model->get('ordertirecostModel');
            $vendors = $order_cost_model->getAllTire(array('where'=>'order_tire='.$_POST['order_tire']));
            
            $vendor_model = $this->model->get('shipmentvendorModel');
            $vendor_list = $vendor_model->getAllVendor(array('order_by'=>'shipment_vendor_name','order'=>'ASC'));

            $str = "";

            if(!$vendors){

                $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $opt .=  '<option  class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }



                $str .= '<tr class="'.$_POST['order_tire'].'">';
                    $str .= '<td><input type="checkbox"  name="chk"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select tabindex="1" class="order_tire_cost_type" name="order_tire_cost_type[]" style="width:100px">';
                    $str .= '<option selected="selected" value="1">Trucking</option>';
                    $str .= '<option  value="2">Barging</option>';
                    $str .= '<option  value="3">Feeder</option>';
                    $str .= '<option  value="4">Thu hộ</option>';
                    $str .= '<option  value="5">Hoa hồng</option>';
                    $str .= '<option  value="6">TTHQ</option>';
                    $str .= '<option  value="7">Khác</option></select></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td> Vendor</td><td><input required="required" type="text" class="vendor" name="vendor[]" autocomplete="off" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a>';
                    $str .= '<ul class="customer_list_id"></ul></td>';
                    //$str .= '<td></td><td> Vendor</td><td><select tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Ngày chi</td>';
                    $str .= '<td><input tabindex="5" class="order_tire_cost_date" type="date"   name="order_tire_cost_date[]" required="required" value="'.date('Y-m-d') .'"></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['order_tire'].'"><td></td><td>Số tiền</td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers order_tire_cost"  name="order_tire_cost[]" value="0"  ></td>';
                                                        
                    $str .= '<td>Nội dung</td>';
                    $str .= '<td rowspan="2"><textarea tabindex="10" class="comment" name="comment[]"  ></textarea></td></tr></table></td></tr>';                                         
                    
            }
            else{

                foreach ($vendors as $v) {
                    $opt = "";
                    foreach ($vendor_list as $vendor) { 
                                                                            
                                if ($vendor->vendor_type == 1) {
                                    $type = "TTHQ";
                                }
                                else if ($vendor->vendor_type == 2) {
                                    $type = "Trucking";
                                }
                                else if ($vendor->vendor_type == 3) {
                                    $type = "Barging";
                                }
                                else if ($vendor->vendor_type == 4) {
                                    $type = "Feeder";
                                }
                                else if ($vendor->vendor_type == 5) {
                                    $type = "Hoa hồng";
                                }
                                else if ($vendor->vendor_type == 6) {
                                    $type = "Thu hộ";
                                }
                                else if ($vendor->vendor_type == 7) {
                                    $type = "Khác";
                                }
                        
                        $slvd = ($vendor->shipment_vendor_id==$v->vendor)?'selected="selected"':null;

                        $opt .=  '<option '.$slvd.' class="'.$vendor->vendor_type .'" value="'.$vendor->shipment_vendor_id .'">'.$vendor->shipment_vendor_name .'</option>';
                           }

                    $payable = $payable_model->getCostsByWhere(array('vendor'=>$v->vendor,'order_tire'=>$v->order_tire,'cost_type'=>$v->order_tire_cost_type));
                    $payed = $payable->pay_money>0?"disabled":null;
                    $payed_mes = $payable->pay_money>0?"Đã thanh toán: ".$this->lib->formatMoney($payable->pay_money):"Chưa thanh toán";

                     $truck = ($v->order_tire_cost_type==1)?'selected="selected"':null;
                     $bar = ($v->order_tire_cost_type==2)?'selected="selected"':null;
                     $fee = ($v->order_tire_cost_type==3)?'selected="selected"':null;
                     $thu = ($v->order_tire_cost_type==4)?'selected="selected"':null;
                     $hh = ($v->order_tire_cost_type==5)?'selected="selected"':null;
                     $tt = ($v->order_tire_cost_type==6)?'selected="selected"':null;
                     $khac = ($v->order_tire_cost_type==7)?'selected="selected"':null;

                     $ten = $vendor_model->getvendor($v->vendor);

                    $lock = null;
                    if ($_SESSION['role_logined'] != 1) {
                        if ($v->order_tire_cost_create_user>0 && $v->order_tire_cost_create_user != $_SESSION['userid_logined']) {
                            $lock = 'disabled';
                        }
                    }

                    $str .= '<tr class="'.$v->order_tire.'">';
                    $str .= '<td><input '.$payed.' type="checkbox" name="chk" tabindex="'.$v->order_tire_cost_type.'" data="'.$v->order_tire .'" class="'.$v->vendor.'" title="'.($v->order_tire_cost).'" '.$lock.'></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->order_tire .'">';
                    $str .= '<td></td><td>Loại chi phí</td>';
                    $str .= '<td><select disabled tabindex="1" class="order_tire_cost_type" name="order_tire_cost_type[]" style="width:100px">';
                    $str .= '<option '.$truck .' value="1">Trucking</option>';
                    $str .= '<option '.$bar .' value="2">Barging</option>';
                    $str .= '<option '.$fee .' value="3">Feeder</option>';
                    $str .= '<option '.$thu .' value="4">Thu hộ</option>';
                    $str .= '<option '.$hh .' value="5">Hoa hồng</option>';
                    $str .= '<option '.$tt .' value="6">TTHQ</option>';
                    $str .= '<option '.$khac .' value="7">Khác</option></select></td>';
                    $str .= '<td style="color:red" colspan="2">'.$payed_mes.'</td></tr>';
                    
                    $str .= '<tr class="'.$v->order_tire .'">';
                    $str .= '<td></td><td> Vendor</td><td><input required="required" type="text" disabled value="'.(isset($ten->shipment_vendor_name)?$ten->shipment_vendor_name:"").'" data="'.$v->vendor.'" class="vendor" name="vendor[]" required="required" autocomplete="off" placeholder="Nhập tên hoặc * để chọn"><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a>';
                    $str .= '<ul class="customer_list_id"></ul></td>';
                    //$str .= '<td></td><td> Vendor</td><td><select disabled tabindex="2" class="vendor" name="vendor[]" style="width:200px">'.$opt.'</select><a style="font-size: 24px; font-weight: bold; color:red" title="Thêm mới" target="_blank" href="'.$this->view->url('shipmentvendor') .'"> + </a></td>';
                    $str .= '<td>Ngày chi</td>';
                    $str .= '<td><input tabindex="5" class="order_tire_cost_date" type="date"   name="order_tire_cost_date[]" required="required" value="'.date('Y-m-d',$v->order_tire_cost_date) .'" '.$lock.'></td></tr>';
                    
                    $str .= '<tr class="'.$v->order_tire.'"><td></td><td>Số tiền </td>'; 
                    $str .= '<td><input tabindex="3" type="text" style="width:120px" class="numbers order_tire_cost"  name="order_tire_cost[]" value="'.$this->lib->formatMoney($v->order_tire_cost) .'"  '.$lock.'></td>';
                                                       
                    $str .= '<td>Nội dung</td>';
                    $str .= '<td rowspan="2"><textarea '.$lock.' tabindex="10" class="comment" name="comment[]"  >'.$v->comment .'</textarea></td></tr></table></td></tr>';                                         
                    
                }
            }

            echo $str;
        }
    }

    public function addordervat(){
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
            $order_tire_list_model = $this->model->get('ordertirelistModel');

            $order_lists = $order_tire_list_model->getAllTire(array('where'=>'order_tire='.$_POST['data']),array('table'=>'order_tire','where'=>'order_tire=order_tire_id'));

            

            $invoice_tire = $_POST['invoice_tire'];
            foreach ($invoice_tire as $v) {
                if (trim($v['invoice_tire_number']) != "0000000") {
                    $cost_data = array(
                        'order_tire' => $_POST['data'],
                        'invoice_tire_number' => trim($v['invoice_tire_number']),
                        'invoice_tire_date' => strtotime(date('d-m-Y',strtotime($v['invoice_tire_date']))),
                    );
                    

                    if ($invoice_tire_model->getInvoiceByWhere(array('order_tire'=>$cost_data['order_tire'],'invoice_tire_number'=>$cost_data['invoice_tire_number']))) {
                        $data_order = $invoice_tire_model->getInvoiceByWhere(array('order_tire'=>$cost_data['order_tire'],'invoice_tire_number'=>$cost_data['invoice_tire_number']));
                        $invoice_tire_model->updateInvoice($cost_data,array('invoice_tire_id'=>$data_order->invoice_tire_id));

                        foreach ($order_lists as $order_list) {
                        
                            $dg = $order_list->check_price_vat==1?$order_list->tire_price_vat*$order_list->vat_percent*0.1/1.1:$order_list->tire_price*$order_list->vat_percent*0.1;

                            $data_detail = array(
                                'order_tire_list'=>$order_list->order_tire_list_id,
                                'invoice_tire_detail_number' => trim($v['invoice_tire_number']),
                                'invoice_tire_detail_date' => strtotime(date('d-m-Y',strtotime($v['invoice_tire_date']))),
                                'invoice_tire_detail_brand'=>$order_list->tire_brand,
                                'invoice_tire_detail_size'=>$order_list->tire_size,
                                'invoice_tire_detail_pattern'=>$order_list->tire_pattern,
                                'invoice_tire_detail_volume'=>$order_list->tire_number,
                                'invoice_tire_detail_price'=>round($dg),
                                'invoice_tire_detail_vat'=>round($dg*0.1),
                                'invoice_tire_detail_create_user'=>$_SESSION['userid_logined'],
                                'order_tire'=>$_POST['data'],
                            );

                            $invoice_tire_detail_model->updateInvoice($data_detail,array('order_tire'=>$_POST['data'],'invoice_tire_detail_number'=>$cost_data['invoice_tire_number']));

                            if (!$invoice_tire_detail_model->getInvoiceByWhere(array('order_tire'=>$_POST['data'],'invoice_tire_detail_number'=>$cost_data['invoice_tire_number']))) {
                                $invoice_tire_detail_model->createInvoice($data_detail);
                            }
                        }
                    }
                    else{
                        $invoice_tire_model->createInvoice($cost_data);

                        foreach ($order_lists as $order_list) {
                        
                            $dg = $order_list->check_price_vat==1?$order_list->tire_price_vat*$order_list->vat_percent*0.1/1.1:$order_list->tire_price*$order_list->vat_percent*0.1;

                            $data_detail = array(
                                'order_tire_list'=>$order_list->order_tire_list_id,
                                'invoice_tire_detail_number' => trim($v['invoice_tire_number']),
                                'invoice_tire_detail_date' => strtotime(date('d-m-Y',strtotime($v['invoice_tire_date']))),
                                'invoice_tire_detail_brand'=>$order_list->tire_brand,
                                'invoice_tire_detail_size'=>$order_list->tire_size,
                                'invoice_tire_detail_pattern'=>$order_list->tire_pattern,
                                'invoice_tire_detail_volume'=>$order_list->tire_number,
                                'invoice_tire_detail_price'=>round($dg),
                                'invoice_tire_detail_vat'=>round($dg*0.1),
                                'invoice_tire_detail_create_user'=>$_SESSION['userid_logined'],
                                'order_tire'=>$_POST['data'],
                            );

                            $invoice_tire_detail_model->createInvoice($data_detail);
                        }

                    }
                }
            }
        }
    }
    public function getordernumber(){
        if(isset($_GET['data'])){
            $order_tire_model = $this->model->get('ordertireModel');
            $order_tires = $order_tire_model->getTire($_GET['data']);

            $str = $order_tires->order_number;

            if ($str == "") {
                $last = "";
                $lasts = $order_tire_model->getAllTire(array('order_by'=>'ABS(SUBSTRING(order_number,4,4)) DESC, ABS(SUBSTRING(order_number,4)) DESC','limit'=>1));
                foreach ($lasts as $tire) {
                    $last = str_replace('lx-', '', $tire->order_number);
                }
                if (substr($last, 4) == 99) {
                    $last = substr($last, 0, 4).'100';
                }
                else{
                    if (intval(substr($last, 2, 2)) != intval(date('m'))) {
                        $last = substr($last, 0, 2).date('m').'00';
                    }

                    $last++;
                }
                
                $str = 'lx-'.$last;
            }

            echo $str;
        }
    }
    public function getordervat(){
        if(isset($_POST['order_tire'])){
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoices = $invoice_tire_model->getAllInvoice(array('where'=>'order_tire='.$_POST['order_tire']));

            $str = "";

            if(!$invoices){

                $str .= '<tr class="'.$_POST['order_tire'].'">';
                    $str .= '<td><input type="checkbox"  name="chk2"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td>Số hóa đơn</td>';
                    $str .= '<td><input tabindex="1" type="text" style="width:120px" class="number invoice_tire_number" required="required"  name="invoice_tire_number[]" value="0000000" maxLength="8" ></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td>Ngày hóa đơn</td>';
                    $str .= '<td><input tabindex="2" class="invoice_tire_date" type="date" name="invoice_tire_date[]" required="required" value="'.date('Y-m-d') .'"></td></tr>';
                    
                    $str .= '</table></td></tr>';                                         
                    
            }
            else{

                foreach ($invoices as $v) {
                    $str .= '<tr class="'.$v->order_tire.'">';
                    $str .= '<td><input type="checkbox" name="chk2" tabindex="'.$v->invoice_tire_id .'" data="'.$v->order_tire .'" title="'.($v->invoice_tire_number).'"></td>';
                    $str .= '<td><table style="width: 100%">';
                    $str .= '<tr class="'.$v->order_tire .'">';
                    $str .= '<td></td><td>Số hóa đơn</td>';
                    $str .= '<td><input tabindex="1" type="text" style="width:120px" class="number invoice_tire_number" required="required" name="invoice_tire_number[]" value="'.($v->invoice_tire_number).'" maxLength="8" ></td></tr>';
                    
                    $str .= '<tr class="'.$_POST['order_tire'] .'">';
                    $str .= '<td></td><td>Ngày hóa đơn</td>';
                    $str .= '<td><input tabindex="2" class="invoice_tire_date" type="date" name="invoice_tire_date[]" required="required" value="'.date('Y-m-d',$v->invoice_tire_date) .'"></td></tr>';
                    
                    $str .= '</table></td>';
                    if ($v->invoice_tire_guid!="") {
                        $str .= '<td><a href="https://van.ehoadon.vn/Lookup?InvoiceGUID='.$v->invoice_tire_guid.'" target="_blank" ><i class="fa fa-search"></i> Tra cứu</a></td>';
                    }
                    else{
                        $str .= '<td><a href="https://van.ehoadon.vn/TCHD?MTC='.$v->invoice_tire_code.'" target="_blank" ><i class="fa fa-search"></i> Tra cứu</a></td>';
                    }
                    
                    $str .= '</tr>';                                         
                    
                }
            }

            echo $str;
        }
    }
    public function deleteordervat(){
        if(isset($_POST['data'])){
            $invoice_tire_model = $this->model->get('invoicetireModel');
            $invoice_tire_model->queryInvoice('DELETE FROM invoice_tire WHERE order_tire = '.$_POST['data'].' AND invoice_tire_number = "'.$_POST['number'].'"');
            $invoice_tire_detail_model = $this->model->get('invoicetiredetailModel');
            $invoice_tire_detail_model->queryInvoice('DELETE FROM invoice_tire_detail WHERE order_tire = '.$_POST['data'].' AND invoice_tire_detail_number = "'.$_POST['number'].'"');

            echo "Xóa thành công";

            date_default_timezone_set("Asia/Ho_Chi_Minh"); 
            $filename = "action_logs.txt";
            $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|invoice_tire|"."\n"."\r\n";
            
            $fh = fopen($filename, "a") or die("Could not open log file.");
            fwrite($fh, $text) or die("Could not write file!");
            fclose($fh);
        }
    }


    public function attachment($id) {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $authUrl = "";

        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/ordertire/uploadDrive';
        $client = new Google_Client();
        $client->setAuthConfig('client_credentials.json');
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/drive");
        $client->setAccessType('offline');        // offline access
        $client->setApprovalPrompt('force');
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $service = new Google_Service_Drive($client);

        $tokenPath = 'google_token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
            $_SESSION['upload_token'] = $accessToken;
        }
        
        // set the access token as part of the client
        if (!empty($_SESSION['upload_token'])) {
          $client->setAccessToken($_SESSION['upload_token']);
          // If there is no previous token or it's expired.
            if ($client->isAccessTokenExpired()) {
                // Refresh the token if possible, else fetch a new one.
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                } else {
                    $authUrl = $client->createAuthUrl();
                }
            }

        } else {
          $authUrl = $client->createAuthUrl();
        }
        $attachment_model = $this->model->get('attachmentModel');

        $attachments = $attachment_model->getAllAttachment(array('where'=>'order_tire='.$id));

        $this->view->data['order'] = $id;
        $this->view->data['attachments'] = $attachments;
        $this->view->data['authUrl'] = $authUrl;
        $this->view->show('ordertire/attachment');
        
    }
    public function uploadDrive() {
        $this->view->disableLayout();

        $attachment_model = $this->model->get('attachmentModel');
        
        $tokenPath = 'google_token.json';
        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/ordertire/uploadDrive';
        $client = new Google_Client();
        $client->setAuthConfig('client_credentials.json');
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/drive");
        $client->setAccessType('offline');        // offline access
        $client->setApprovalPrompt('force');
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $service = new Google_Service_Drive($client);

        /************************************************
         * If we have a code back from the OAuth 2.0 flow,
         * we need to exchange that with the
         * Google_Client::fetchAccessTokenWithAuthCode()
         * function. We store the resultant access token
         * bundle in the session, and redirect to ourself.
         ************************************************/
        if (isset($_GET['code'])) {
          $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
          $client->setAccessToken($token);
          // store in the session also
          $_SESSION['upload_token'] = $token;
          // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
          // redirect back to the example
          echo "<script>window.close();</script>";
        }
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
            $_SESSION['upload_token'] = $accessToken;
        }

        $file = new Google_Service_Drive_DriveFile();


        $output_dir = "public/files/";
        if(isset($_FILES["myfile"]))
        {
            $ret = array();

            $error =$_FILES["myfile"]["error"];
            //You need to handle  both cases
            //If Any browser does not support serializing of multiple files using FormData() 
            if(!is_array($_FILES["myfile"]["name"])) //single file
            {
                $fileName = $_FILES["myfile"]["name"];
                $fullpath = $output_dir.$fileName;
                $file_info = pathinfo($fullpath);
                $uploaded_filename = $file_info['filename'];

                $count = 1;                 
                while (file_exists($fullpath)) {
                  $info = pathinfo($fullpath);
                  $uploaded_filename .= '(' . $count++ . ')';
                  $fullpath = $info['dirname'] . '/' . $uploaded_filename . '.' . $info['extension'];
                }
                move_uploaded_file($_FILES["myfile"]["tmp_name"],$fullpath);
                
                $file->setName($uploaded_filename);
                $result = $service->files->create($file, array(
                  'data' => file_get_contents($fullpath),
                  'mimeType' => 'application/octet-stream',
                  'uploadType' => 'media'
                ));

                $fileId = $result->id;
                $service->getClient()->setUseBatch(true);
                try {
                    $batch = $service->createBatch();

                    $domainPermission = new Google_Service_Drive_Permission(array(
                        'type' => 'anyone',
                        'role' => 'reader'
                    ));
                    $request = $service->permissions->create(
                        $fileId, $domainPermission, array('fields' => 'id'));
                    $batch->add($request, 'anyone');
                    $batch->execute();

                } finally {
                    $service->getClient()->setUseBatch(false);
                }

                $ret[] = $result->id;

                $data = array(
                    'order_tire'=>$_POST['order'],
                    'attachment_date'=>time(),
                    'attachment_name'=>$result->name,
                    'attachment_link'=>'https://drive.google.com/open?id='.$result->id,
                    'attachment_user'=>$_SESSION['userid_logined']
                );
                $attachment_model->createAttachment($data);

                unlink($fullpath);
            }
            else  //Multiple files, file[]
            {
              $fileCount = count($_FILES["myfile"]["name"]);
              for($i=0; $i < $fileCount; $i++)
              {
                $fileName = $_FILES["myfile"]["name"][$i];
                $fullpath = $output_dir.$fileName;
                $file_info = pathinfo($fullpath);
                $uploaded_filename = $file_info['filename'];

                $count = 1;                 
                while (file_exists($fullpath)) {
                  $info = pathinfo($fullpath);
                  $uploaded_filename .= '(' . $count++ . ')';
                  $fullpath = $info['dirname'] . '/' . $uploaded_filename . '.' . $info['extension'];
                }
                move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$fullpath);

                $file->setName($uploaded_filename);
                $result = $service->files->create($file, array(
                  'data' => file_get_contents($fullpath),
                  'mimeType' => 'application/octet-stream',
                  'uploadType' => 'media'
                ));

                $fileId = $result->id;
                $service->getClient()->setUseBatch(true);
                try {
                    $batch = $service->createBatch();

                    $domainPermission = new Google_Service_Drive_Permission(array(
                        'type' => 'anyone',
                        'role' => 'reader'
                    ));
                    $request = $service->permissions->create(
                        $fileId, $domainPermission, array('fields' => 'id'));
                    $batch->add($request, 'anyone');
                    $batch->execute();

                } finally {
                    $service->getClient()->setUseBatch(false);
                }

                $ret[] = $result->id;

                $data = array(
                    'order_tire'=>$_POST['order'],
                    'attachment_date'=>time(),
                    'attachment_name'=>$result->name,
                    'attachment_link'=>'https://drive.google.com/open?id='.$result->id,
                    'attachment_user'=>$_SESSION['userid_logined']
                );
                $attachment_model->createAttachment($data);
                
                unlink($fullpath);
              }
            
            }

            echo json_encode($ret);
            
         }

        // Now lets try and send the metadata as well using multipart!
          
    }
    public function deleteDrive(){
        $this->view->disableLayout();

        $attachment_model = $this->model->get('attachmentModel');

        $tokenPath = 'google_token.json';
        $redirect_uri = 'https://' . $_SERVER['HTTP_HOST'] . '/ordertire/uploadDrive';
        $client = new Google_Client();
        $client->setAuthConfig('client_credentials.json');
        $client->setRedirectUri($redirect_uri);
        $client->addScope("https://www.googleapis.com/auth/drive");
        $client->setAccessType('offline');        // offline access
        $client->setApprovalPrompt('force');
        $client->setIncludeGrantedScopes(true);   // incremental auth
        $service = new Google_Service_Drive($client);

        /************************************************
         * If we have a code back from the OAuth 2.0 flow,
         * we need to exchange that with the
         * Google_Client::fetchAccessTokenWithAuthCode()
         * function. We store the resultant access token
         * bundle in the session, and redirect to ourself.
         ************************************************/
        if (isset($_GET['code'])) {
          $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
          $client->setAccessToken($token);
          // store in the session also
          $_SESSION['upload_token'] = $token;
          // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
          // redirect back to the example
          echo "<script>window.close();</script>";
        }
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
            $_SESSION['upload_token'] = $accessToken;
        }

        

        $output_dir = "https://drive.google.com/open?id=";
        if(isset($_POST["op"]) && $_POST["op"] == "delete" && isset($_POST['name']))
        {
            $fileName =$_POST['name'];
            $filePath = $output_dir. $fileName;
            $attachment_model->queryAttachment('DELETE FROM attachment WHERE attachment_link ="'.$output_dir. $fileName.'"');

            $service->files->delete($fileName);
        }
    }

    public function contract() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $customer_model = $this->model->get('customerModel');
        $order_tire_model = $this->model->get('ordertireModel');

        $customers = $customer_model->getCustomer($this->registry->router->param_id);


        $info = $this->registry->router->addition;
        
        $arr = explode('@', $info);

        $data = array(
            'contract_number'=>str_replace('$', '/', $arr[1]),
            'contract_date'=>strtotime($arr[0]),
            'contract_pay_1'=>$arr[2],
            'contract_pay_2'=>$arr[3],
            'contract_end_date'=>strtotime($arr[4]),
        );
        $order_tire_model->updateTire($data,array('order_tire_id'=>$this->registry->router->page));

        $this->view->data['company'] = strtoupper($customers->company_name);
        $this->view->data['mst'] = $customers->mst;
        $this->view->data['address'] = $customers->customer_address;
        $this->view->data['phone'] = $customers->company_phone;
        $this->view->data['fax'] = $customers->customer_fax;
        $this->view->data['bank_number'] = $customers->account_number;
        $this->view->data['bank'] = $customers->customer_bank_name;
        $this->view->data['name'] = $customers->director;

        $this->view->data['contract_date'] = explode('-', $arr[0]);
        $this->view->data['contract_number'] = str_replace('$', '/', $arr[1]);
        $this->view->data['contract_pay'] = $arr[2];
        $this->view->data['contract_pay2'] = $arr[3];
        $this->view->data['contract_valid'] = str_replace('-', '/', $arr[4]);
                
        $this->view->show('ordertire/contract');
    }

    public function bill() {
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;

        $order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        $this->view->data['orders'] = $orders;
        $this->view->data['customers'] = $customers;
        $this->view->data['order_types'] = $order_types;
                
        $this->view->show('ordertire/bill');
    }

   

    function bangke(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'Đơn vị bán hàng: CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A2', 'Địa chỉ: Số 29, Quốc lộ 51, Ấp Đồng, Phước Tân, Biên Hòa, Đồng Nai')
                ->setCellValue('A3', 'MST: 3603295302')
                ->setCellValue('A4', 'Điện thoại: 02513 937 677')
                ->setCellValue('A6', 'BẢNG KÊ')
                ->setCellValue('G7', 'TP Biên Hòa, Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
               ->setCellValue('A9', 'Kính gửi: '.$customers->company_name)
               ->setCellValue('A10', 'Địa chỉ: '.$customers->customer_address)
               ->setCellValue('A11', 'MST: '.$customers->mst)
               ->setCellValue('A12', 'Đề nghị thanh toán: Tiền lốp xe')
               ->setCellValue('A13', 'STT')
               ->setCellValue('B13', 'TÊN HÀNG')
               ->setCellValue('C13', 'LOẠI HÀNG')
               ->setCellValue('D13', 'ĐƠN VỊ')
               ->setCellValue('E13', 'SỐ LƯỢNG')
               ->setCellValue('F13', 'ĐƠN GIÁ')
               ->setCellValue('G13', 'THÀNH TIỀN')
               ->setCellValue('H13', 'GHI CHÚ');
               

            

            
            
            

            if ($order_types) {

                $hang = 14;
                $i=1;

                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe')
                        ->setCellValue('C' . $hang, $row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('D' . $hang, 'Cái')
                        ->setCellValue('E' . $hang, $row->tire_number)
                        ->setCellValue('F' . $hang, $row->tire_price)
                        ->setCellValue('G' . $hang, '=E'.$hang.'*F'.$hang);
                     $hang++;


                  }

                  $f = $hang;

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng cộng')
                       ->setCellValue('G'.$hang, '=SUM(G7:G'.($hang-1).')');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $hang++;

                if ($orders->vat>0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'VAT')
                        ->setCellValue('F'.$hang, $orders->vat_percent.'%')
                       ->setCellValue('G'.$hang, '=G'.($hang-1).'*F'.$hang);

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                       $hang++;
                  }

                  if ($orders->discount>0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Chiết khấu')
                       ->setCellValue('G'.$hang, $orders->discount+$orders->reduce);

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                       $hang++;

                       $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng thanh toán')
                       ->setCellValue('G'.$hang, '=G'.$f.'+G'.($f+1).'-G'.($f+2));

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  }
                  
                  if ($orders->discount=="" || $orders->discount==0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Tổng thanh toán')
                       ->setCellValue('G'.$hang, '=G'.$f.'+G'.($f+1));

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang).':E'.($hang));
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                  }

                    $objPHPExcel->getActiveSheet()->getStyle('A6:I'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+3), 'XÁC NHẬN KHÁCH HÀNG')
                        ->setCellValue('G'.($hang+3), 'NGƯỜI LẬP');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+3).':D'.($hang+3));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+3).':H'.($hang+3));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3).':H'.($hang+3))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $objPHPExcel->getActiveSheet()->getStyle('A'.$hang.':H'.($hang+3))->applyFromArray(
                        array(
                            
                            'font' => array(
                                'bold'  => true,
                                'color' => array('rgb' => '000000')
                            )
                        )
                    );

          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $objPHPExcel->getActiveSheet()->getStyle('A13:I'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:H2');
            $objPHPExcel->getActiveSheet()->mergeCells('A3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('A4:H4');
            $objPHPExcel->getActiveSheet()->mergeCells('A6:H6');
            $objPHPExcel->getActiveSheet()->mergeCells('G7:H7');

            $objPHPExcel->getActiveSheet()->mergeCells('A9:H9');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A10:H10');
            $objPHPExcel->getActiveSheet()->mergeCells('A11:H11');
            $objPHPExcel->getActiveSheet()->mergeCells('A12:H12');

            $objPHPExcel->getActiveSheet()->mergeCells('H13:I13');

            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A6:H6')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A13:H13')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle("A6")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A1:H13')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            

            $objPHPExcel->getActiveSheet()->getStyle('F14:G'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("List")
                            ->setSubject("List")
                            ->setDescription("List.")
                            ->setKeywords("List")
                            ->setCategory("List");
            $objPHPExcel->getActiveSheet()->setTitle("Bang ke");

            $objPHPExcel->getActiveSheet()->freezePane('A14');
            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BẢNG KÊ.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

   

    function invoice(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');

        $orders = $tire_order_model->getTire($order);

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        $trugiam = $orders->discount+$orders->reduce;
        if ($trugiam<0) {
            $trugiam = 0;
        }
        $giam = $trugiam/$orders->order_tire_number;
        $giam = $giam/1.1;
        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'HÓA ĐƠN GTGT')
                ->setCellValue('A2', 'Liên 2: Giao cho người mua')
                ->setCellValue('G3', 'Ngày '.date('d').' tháng '.date('m').' năm '.date('Y').'')
                ->setCellValue('A5', 'Đơn vị bán hàng: ')
                ->setCellValue('B5', 'CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A6', 'MST: ')
                ->setCellValue('B6', "'3603295302")
                ->setCellValue('A7', 'Địa chỉ: ')
                ->setCellValue('B7', 'Số 29, Quốc lộ 51, Ấp Đồng, Xã Phước Tân, TP.Biên Hòa, Đồng Nai')
                ->setCellValue('A8', 'Điện thoại: ')
                ->setCellValue('B8', '02513 937 677')
                ->setCellValue('C8', 'STK: ')
                ->setCellValue('D8', '200970509 ')
                ->setCellValue('E8', 'ACB Biên Hòa')
                ->setCellValue('A9', 'Họ tên người mua hàng: ')
                ->setCellValue('C9', $customers->customer_name)
                ->setCellValue('A10', 'Tên đơn vị: ')
               ->setCellValue('B10', $customers->company_name)
               ->setCellValue('A11', 'Mã số thuế: ')
               ->setCellValue('B11', "'".$customers->mst)
               ->setCellValue('A12', 'Địa chỉ: ')
               ->setCellValue('B12', $customers->customer_address)
               ->setCellValue('A13', 'STK: ')
               ->setCellValue('A14', 'STT')
               ->setCellValue('B14', 'Tên Hàng Hóa Dịch Vụ')
               ->setCellValue('F14', 'Đơn Vị Tính')
               ->setCellValue('G14', 'Số Lượng')
               ->setCellValue('H14', 'Đơn Giá')
               ->setCellValue('I14', 'Thành Tiền')
               ->setCellValue('A16', '1')
               ->setCellValue('B16', '2')
               ->setCellValue('F16', '3')
               ->setCellValue('G16', '4')
               ->setCellValue('H16', '5')
               ->setCellValue('I16', '6 = 4x5');
               
            
            

            if ($order_types) {

                $hang = 17;
                $i=1;
                $tong = 0;
                foreach ($order_types as $row) {
                    $dg = ($orders->vat_percent>0?($orders->check_price_vat==1?$row->tire_price_vat*$orders->vat_percent*0.1/1.1:$row->tire_price*$orders->vat_percent*0.1):$row->tire_price);
                    $dg = $dg-$giam;
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, 'Lốp xe '.$row->tire_brand_name.' '.$row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('F' . $hang, (substr($row->tire_size_number, -2)=='.5'?'Cái':'Bộ'))
                        ->setCellValue('G' . $hang, $row->tire_number)
                        ->setCellValue('H' . $hang, $dg)
                        ->setCellValue('I' . $hang, '=G'.$hang.'*H'.$hang);
                     $hang++;

                     $tong += $row->tire_number*$dg;

                     

                  }

                  $objPHPExcel->getActiveSheet()->getStyle('B17:I'.$hang)->applyFromArray(
                        array(
                            'font' => array(
                                'color' => array('rgb' => '5bc0de')
                            ),
                        )
                    );

                  $tong = round($tong+($tong*0.1));

                  for ($j=0; $j < 5; $j++) { 
                      $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' .$hang, null)
                        ->setCellValue('F' . $hang, null)
                        ->setCellValue('G' . $hang, null)
                        ->setCellValue('H' . $hang, null)
                        ->setCellValue('I' . $hang, null);
                     $hang++;
                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('D'.($hang+1), 'Cộng tiền hàng:')
                       ->setCellValue('I'.($hang+1), '=SUM(I17:I'.($hang-2).')')
                       ->setCellValue('D'.($hang+2), 'Tiền thuế GTGT:')
                       ->setCellValue('I'.($hang+2), '=I'.($hang+1).'*10%')
                       ->setCellValue('D'.($hang+3), 'Tổng cộng tiền thanh toán:')
                       ->setCellValue('I'.($hang+3), '=I'.($hang+2).'+I'.($hang+1))
                       ->setCellValue('A'.($hang+4), 'Viết bằng chữ:')
                       ->setCellValue('B'.($hang+4), $this->lib->convert_number_to_words($tong).' đồng');

                    $objRichText = new PHPExcel_RichText();
                    $textBold = $objRichText->createTextRun("Thuế suất GTGT: ");

                    $under = $objRichText->createTextRun('  10%');
                    $under->getFont()->setBold(true);
                    $under->getFont()->setItalic(true);

                    $objPHPExcel->getActiveSheet()->getCell('A'.($hang+2))->setValue($objRichText);


                    $objPHPExcel->getActiveSheet()->getStyle('A14:I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'outline' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                  

                    $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+7), '(Kí ghi rõ họ tên)')
                        ->setCellValue('G'.($hang+7), '(Kí ghi rõ họ tên, đóng dấu)');

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+7).':B'.($hang+7));
                    $objPHPExcel->getActiveSheet()->mergeCells('G'.($hang+7).':H'.($hang+7));

                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+7).':H'.($hang+7))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);


          }

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:I1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:I2');
            $objPHPExcel->getActiveSheet()->mergeCells('G3:H3');
            $objPHPExcel->getActiveSheet()->mergeCells('B4:I4');
            $objPHPExcel->getActiveSheet()->mergeCells('B5:I5');
            $objPHPExcel->getActiveSheet()->mergeCells('B6:I6');

            $objPHPExcel->getActiveSheet()->mergeCells('B7:I7');
            $objPHPExcel->getActiveSheet()->mergeCells('E8:I8');
            $objPHPExcel->getActiveSheet()->mergeCells('A9:B9');
            $objPHPExcel->getActiveSheet()->mergeCells('C9:I9');
            $objPHPExcel->getActiveSheet()->mergeCells('B10:I10');
            $objPHPExcel->getActiveSheet()->mergeCells('B11:I11');
            $objPHPExcel->getActiveSheet()->mergeCells('B12:I12');
            $objPHPExcel->getActiveSheet()->mergeCells('B13:I13');
            $objPHPExcel->getActiveSheet()->mergeCells('B14:E15');
            $objPHPExcel->getActiveSheet()->mergeCells('A14:A15');
            $objPHPExcel->getActiveSheet()->mergeCells('F14:F15');
            $objPHPExcel->getActiveSheet()->mergeCells('G14:G15');
            $objPHPExcel->getActiveSheet()->mergeCells('H14:H15');
            $objPHPExcel->getActiveSheet()->mergeCells('I14:I15');
            $objPHPExcel->getActiveSheet()->mergeCells('B16:E16');



            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:A2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle("A1")->getFont()->setSize(16);

            $objPHPExcel->getActiveSheet()->getStyle('A14:I16')->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+1).':I'.($hang+4))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

            $objPHPExcel->getActiveSheet()->getStyle('A1')->applyFromArray(
                array(
                    
                    'font' => array(
                        'bold'  => true,
                        'color' => array('rgb' => '000000')
                    )
                )
            );

            
            $objPHPExcel->getActiveSheet()->getStyle('B5:B13')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('D8:E8')->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('I17:I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('B'.($hang+4).':I'.($hang+4))->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => '5bc0de')
                    ),
                )
            );

            $objPHPExcel->getActiveSheet()->mergeCells('B'.($hang+4).':I'.($hang+4));



            

            $objPHPExcel->getActiveSheet()->getStyle('H17:I'.$hang)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getStyle('I'.$hang.':I'.$highestRow)->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
            $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(16);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(15);

            $objPHPExcel->getActiveSheet()->getStyle("A1:I".($highestRow+1))->getFont()->setName('Times New Roman');

            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Invoice")
                            ->setSubject("Invoice")
                            ->setDescription("Invoice.")
                            ->setKeywords("Invoice")
                            ->setCategory("Invoice");
            $objPHPExcel->getActiveSheet()->setTitle("Hoa don");

            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= HÓA ĐƠN NHÁP.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
        
    }

    function bienban(){
        $this->view->disableLayout();
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }

        $order = $this->registry->router->param_id;
        
        $tire_order_model = $this->model->get('ordertireModel');
        $tire_order_list_model = $this->model->get('ordertirelistModel');
        $customer_model = $this->model->get('customerModel');
        $staff_model = $this->model->get('staffModel');

        $orders = $tire_order_model->getTire($order);

        $staffs = $staff_model->getStaffByWhere(array('account'=>$orders->sale));

        if($orders->delivery_date>0){
            $ngay = date('d',$orders->delivery_date);
            $thang = date('m',$orders->delivery_date);
            $nam = date('Y',$orders->delivery_date);
        }
        else{
            $ngay = date('d');
            $thang = date('m');
            $nam = date('Y');
        }
        
        $order_number = $orders->order_number!=""?$orders->order_number:"lx-".substr($nam, -2).$thang."...";

        $customers = $customer_model->getCustomer($orders->customer);

        $data = array('where'=>'order_tire = '.$order);
        $join = array('table'=>'tire_pattern, tire_brand, tire_size','where'=> 'tire_brand_id=tire_brand AND tire_size_id=tire_size AND tire_pattern_id=tire_pattern');
        $order_types = $tire_order_list_model->getAllTire($data,$join);

        
            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $objPHPExcel = new PHPExcel();

            

            $index_worksheet = 0; //(worksheet mặc định là 0, nếu tạo nhiều worksheet $index_worksheet += 1)
            $objPHPExcel->setActiveSheetIndex($index_worksheet)
                ->setCellValue('A1', 'CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('E1', 'CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM')
                ->setCellValue('A2', 'Số: '.$order_number.' /BB')
                ->setCellValue('E2', 'Độc lập - Tự do - Hạnh phúc')
                ->setCellValue('A4', 'BIÊN BẢN')
                ->setCellValue('A5', 'BÀN GIAO TÀI SẢN và XÁC NHẬN CÔNG NỢ')
                ->setCellValue('A7', 'Hôm nay, ngày '.$ngay.' tháng '.$thang.' năm '.$nam.' tại kho lốp công ty Việt Tra de đã tiến hành bàn giao tài sản giữa:')
                
                ->setCellValue('A8', 'A/ Bên bán: ')
                ->setCellValue('C8', 'CÔNG TY TNHH VIỆT TRA DE')
                ->setCellValue('A9', '- Mã số thuế: 3603295302')
                ->setCellValue('F9', 'Hotline: 0283 500 9000')
                ->setCellValue('A10', '- Địa chỉ: Số 29, Quốc lộ 51, Ấp Đồng, Xã Phước Tân, TP.Biên Hòa, Đồng Nai')
                ->setCellValue('A11', '- Ông/bà: '.$staffs->staff_name)
                ->setCellValue('E11', 'Chức vụ: ')
                ->setCellValue('F11', 'SĐT: '.$staffs->staff_phone)
                ->setCellValue('A12', 'B/ Bên mua: ')
                ->setCellValue('C12', mb_strtoupper($customers->company_name, "UTF-8"))
                ->setCellValue('A13', '- Mã số thuế: '.$customers->mst)
                ->setCellValue('A14', '- Địa chỉ: '.$customers->customer_address)
                ->setCellValue('A15', '- Ông/bà: '.$customers->customer_contact)
                ->setCellValue('E15', 'CMND: ')
                ->setCellValue('F15', 'SĐT: '.$customers->customer_phone)
                ->setCellValue('A16', 'NỘI DUNG BÀN GIAO: ')
                ->setCellValue('A17', 'Bên A đã tiến hành bàn giao tài sản cho bên B theo bảng thống kê sau: ')
                ->setCellValue('A18', 'Bảng thống kê tài sản bàn giao')
               ->setCellValue('A19', 'STT')
               ->setCellValue('B19', 'TÊN HÀNG')
               ->setCellValue('C19', 'LOẠI HÀNG')
               ->setCellValue('D19', 'SL')
               ->setCellValue('E19', 'ĐƠN GIÁ')
               ->setCellValue('F19', 'THÀNH TIỀN')
               ->setCellValue('G19', 'GHI CHÚ');
               
            
            

            if ($order_types) {

                $hang = 20;
                $i=1;
                $tong = 0;
                foreach ($order_types as $row) {
                    
                    //$objPHPExcel->setActiveSheetIndex(0)->getStyle('B'.$hang)->getNumberFormat()->setFormatCode( PHPExcel_Style_NumberFormat::FORMAT_TEXT );
                     $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $hang, $i++)
                        ->setCellValueExplicit('B' . $hang, $row->tire_brand_name)
                        ->setCellValueExplicit('C' . $hang, $row->tire_size_number.' '.$row->tire_pattern_name)
                        ->setCellValue('D' . $hang, $row->tire_number)
                        ->setCellValue('E' . $hang, ($orders->check_price_vat==1?$row->tire_price_vat:$row->tire_price))
                        ->setCellValue('F' . $hang, '=E'.$hang.'*D'.$hang)
                        ->setCellValue('G' . $hang, ($orders->check_price_vat==1?'HD('.$orders->vat_percent.')':""));
                     $hang++;

                     $tong += $row->tire_number*($orders->check_price_vat==1?$row->tire_price_vat:$row->tire_price);

                     

                  }

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.$hang, 'Cộng')
                       ->setCellValue('D'.$hang, '=SUM(D20:D'.($hang-1).')')
                       ->setCellValue('F'.$hang, '=SUM(F20:F'.($hang-1).')');

                $objPHPExcel->getActiveSheet()->mergeCells('A'.$hang.':C'.$hang);

                $objPHPExcel->getActiveSheet()->getStyle('A19:G'.$hang)->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );

                $sohang = $hang;

                  if ($orders->check_price_vat!=1 && $orders->vat>0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+1), 'VAT')
                       ->setCellValue('F'.($hang+1), $orders->vat);

                       $tong +=  $orders->vat;

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+1).':C'.($hang+1));
                       $hang++;
                  }

                  if ($orders->discount+$orders->reduce != 0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+1), 'Giảm trừ')
                       ->setCellValue('F'.($hang+1), ($orders->discount+$orders->reduce));

                       $tong -= ($orders->discount+$orders->reduce);

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+1).':C'.($hang+1));
                       $hang++;
                  }

                  if ($orders->warranty != 0) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+1), 'Bảo hành')
                       ->setCellValue('F'.($hang+1), ($orders->warranty));

                       $tong -= ($orders->warranty);

                       $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+1).':C'.($hang+1));
                       $hang++;
                  }

                  if (($orders->check_price_vat!=1 && $orders->vat>0) || ($orders->discount+$orders->reduce != 0) || ($orders->warranty != 0)) {
                      $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+1), 'Tổng cộng')
                       ->setCellValue('F'.($hang+1), $tong);

                    $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+1).':C'.($hang+1));

                    $objPHPExcel->getActiveSheet()->getStyle('A19:G'.($hang+1))->applyFromArray(
                        array(
                            
                            'borders' => array(
                                'allborders' => array(
                                  'style' => PHPExcel_Style_Border::BORDER_THIN
                                )
                            )
                        )
                    );
                  }

                  

                $objPHPExcel->getActiveSheet()->mergeCells('G20:G'.$hang);
                $objPHPExcel->getActiveSheet()->getStyle('A20:D'.($hang+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A19:D'.($hang+1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G20:G'.($hang+1))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('G20:G'.($hang+1))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('E20:F'.($hang+1))->getNumberFormat()->setFormatCode("#,##0_);[Black](#,##0)");
                $objPHPExcel->getActiveSheet()->getRowDimension(($hang+1))->setRowHeight(22);

                

                $objPHPExcel->getActiveSheet()->getStyle('A'.$sohang.':G'.($hang+1))->getFont()->setBold(true);

                $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+2), 'Số tiền bằng chữ:')
                       ->setCellValue('C'.($hang+2), $this->lib->convert_number_to_words($tong).' đồng.');

                    $objPHPExcel->getActiveSheet()->getStyle('C'.($hang+2))->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('C'.($hang+2))->getFont()->setItalic(true);
                    $objPHPExcel->getActiveSheet()->mergeCells('C'.($hang+2).':G'.($hang+2));

                  $objPHPExcel->setActiveSheetIndex($index_worksheet)
                        ->setCellValue('A'.($hang+3), 'Bên B sẽ thanh toán toàn bộ số tiền trên cho bên A trước khi nhận hàng, bằng tiền mặt (có xác nhận giữa 2 bên) hoặc chuyển khoản theo thông tin sau:')
                        ->setCellValue('B'.($hang+4), 'TK1: CONG TY TNHH VIET TRA DE  -  67210000476025  BIDV Nam Đồng Nai.')
                        ->setCellValue('B'.($hang+5), 'TK2: PHUNG THI PHUONG   -  67210000476326  BIDV Nam Đồng Nai.')
                        ->setCellValue('A'.($hang+6), '(Khách hàng thanh toán chuyển khoản vui lòng ghi rõ mã số đơn hàng khi thanh toán).')
                        ->setCellValue('A'.($hang+7), 'Biên bản này lập thành 3 bản có giá trị như nhau. Mỗi bên giữ một bản.')
                        ->setCellValue('A'.($hang+10), 'BÊN GIAO HÀNG')
                        ->setCellValue('C'.($hang+10), 'BÊN NHẬN HÀNG')
                        ->setCellValue('F'.($hang+10), 'BÊN VẬN CHUYỂN')
                        ->setCellValue('A'.($hang+11), '(Ký, họ tên)')
                        ->setCellValue('C'.($hang+11), '(Ký, đóng dấu & ghi rõ họ tên)')
                        ->setCellValue('F'.($hang+11), '(Ký & ghi rõ họ tên, BS xe, SĐT)')
                        ->setCellValue('A'.($hang+16), $staffs->staff_name);

                $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+10).':B'.($hang+10));
                $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+16).':B'.($hang+16));
                $objPHPExcel->getActiveSheet()->mergeCells('C'.($hang+10).':E'.($hang+10));
                $objPHPExcel->getActiveSheet()->mergeCells('F'.($hang+10).':G'.($hang+10));
                $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+11).':B'.($hang+11));
                $objPHPExcel->getActiveSheet()->mergeCells('C'.($hang+11).':E'.($hang+11));
                $objPHPExcel->getActiveSheet()->mergeCells('F'.($hang+11).':G'.($hang+11));
                $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+10).':G'.($hang+10))->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+16).':G'.($hang+16))->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+10).':G'.($hang+16))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+10).':G'.($hang+16))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                $objPHPExcel->getActiveSheet()->getRowDimension(($hang+3))->setRowHeight(36);
                  
          }

          $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+6))->applyFromArray(
                array(
                    'font' => array(
                        'bold'  => true,
                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,
                    )
                )
            );

            $highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();

            $highestRow ++;

            $objPHPExcel->getActiveSheet()->mergeCells('A1:C1');
            $objPHPExcel->getActiveSheet()->mergeCells('A2:C2');
            $objPHPExcel->getActiveSheet()->mergeCells('E1:G1');
            $objPHPExcel->getActiveSheet()->mergeCells('E2:G2');
            $objPHPExcel->getActiveSheet()->getStyle('A1:G1')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('E2')->applyFromArray(

                array(

                    

                    'font' => array(

                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,

                    )

                )

            );

            $objPHPExcel->getActiveSheet()->mergeCells('A4:G4');
            $objPHPExcel->getActiveSheet()->mergeCells('A5:G5');
            $objPHPExcel->getActiveSheet()->getStyle('A4:G5')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A1:G5')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1:G5')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->mergeCells('A8:B8');
            $objPHPExcel->getActiveSheet()->mergeCells('C8:G8');
            $objPHPExcel->getActiveSheet()->getStyle('A8:G8')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A12:B12');
            $objPHPExcel->getActiveSheet()->mergeCells('C12:G12');
            $objPHPExcel->getActiveSheet()->getStyle('A12:G12')->getFont()->setBold(true);

            $objPHPExcel->getActiveSheet()->getStyle('A8')->applyFromArray(
                array(
                    'font' => array(
                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A12')->applyFromArray(
                array(
                    'font' => array(
                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle('A16')->applyFromArray(
                array(
                    'font' => array(
                        'underline' => PHPExcel_Style_Font::UNDERLINE_SINGLE,
                    )
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('A19:G19')->applyFromArray(
                array(
                    'fill' => array(
                        'type' => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => 'c6f2c8')
                    ),
                    'font' => array(
                        'color' => array('rgb' => 'e60c52')
                    )
                )
            );

            $objPHPExcel->getActiveSheet()->mergeCells('A16:G16');
            $objPHPExcel->getActiveSheet()->getStyle('A16')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->mergeCells('A17:G17');
            $objPHPExcel->getActiveSheet()->mergeCells('A18:G18');
            $objPHPExcel->getActiveSheet()->getStyle('A18:G19')->getFont()->setBold(true);
            $objPHPExcel->getActiveSheet()->getStyle('A18:G19')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A18:G19')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->mergeCells('A7:G7');
            $objPHPExcel->getActiveSheet()->mergeCells('A14:G14');
            $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+3).':G'.($hang+3));
            $objPHPExcel->getActiveSheet()->mergeCells('A'.($hang+6).':G'.($hang+6));
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A14')->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+3))->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getStyle('A'.($hang+6))->getAlignment()->setWrapText(true);
            $objPHPExcel->getActiveSheet()->getRowDimension(7)->setRowHeight(-1);
            $objPHPExcel->getActiveSheet()->getRowDimension(14)->setRowHeight(-1);
            $objPHPExcel->getActiveSheet()->getRowDimension(($hang+6))->setRowHeight(-1);
            
            
            $objPHPExcel->getActiveSheet()->getStyle("A1:G".($highestRow+1))->getFont()->setSize(13);
            $objPHPExcel->getActiveSheet()->getStyle("A4:A5")->getFont()->setSize(18);
            
            $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(18);
            $objPHPExcel->getActiveSheet()->getRowDimension('6')->setRowHeight(20);
            $objPHPExcel->getActiveSheet()->getRowDimension('19')->setRowHeight(26);
            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(18);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(5);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(5);

            $objPHPExcel->getActiveSheet()->getStyle("A1:G".($highestRow+1))->getFont()->setName('Times New Roman');

            $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);    
            $objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0); 
            $objPHPExcel->getActiveSheet()->getPageMargins()->setRight(0.2); 
            $objPHPExcel->getActiveSheet()->getPageMargins()->setLeft(0.4); 
            $objPHPExcel->getActiveSheet()->getPageMargins()->setTop(0.4); 
            $objPHPExcel->getActiveSheet()->getPageMargins()->setBottom(0); 
            $objPHPExcel->getActiveSheet()->getPageMargins()->setFooter(0); 

            include('lib/phpqrcode/qrlib.php'); 

            $tempDir = "public/images/"; 
            $codeContents = 'https://www.viet-trade.org/onlineservices/tireorder/00'.$order.'1';
            $fileName = 'qr_'.md5($codeContents).'.png'; 
            $pngAbsoluteFilePath = $tempDir.$fileName; 
            QRcode::png($codeContents, $pngAbsoluteFilePath); 
            // Add a drawing to the header
            $objDrawing = new PHPExcel_Worksheet_HeaderFooterDrawing();
            $objDrawing->setName('Image');
            $objDrawing->setPath($pngAbsoluteFilePath);
            $objDrawing->setHeight(50);
            $objPHPExcel->getActiveSheet()->getHeaderFooter()->addImage($objDrawing, PHPExcel_Worksheet_HeaderFooter::IMAGE_FOOTER_LEFT);
            //$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&G&');

            // $objDrawing = new PHPExcel_Worksheet_Drawing();
            // $objDrawing->setName("Image");
            // $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
            // $logo = "public/images/logo-viet-trade.png";
            // $objDrawing->setPath($logo);
            // $objDrawing->setHeight(40);  
            // $objDrawing->setWidth(130);  
            // $objDrawing->setCoordinates('G3');

            $objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&G&"-,Italic"&K848a91Please visit our website to view the status of your order. https://www.viet-trade.org');


            // Set properties
            $objPHPExcel->getProperties()->setCreator("Viet Trade")
                            ->setLastModifiedBy($_SESSION['user_logined'])
                            ->setTitle("Invoice")
                            ->setSubject("Invoice")
                            ->setDescription("Invoice.")
                            ->setKeywords("Invoice")
                            ->setCategory("Invoice");
            $objPHPExcel->getActiveSheet()->setTitle("Bien ban ban giao");

            $objPHPExcel->setActiveSheetIndex(0);



            

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment; filename= BBBG.xlsx");
            header("Cache-Control: max-age=0");
            ob_clean();
            $objWriter->save("php://output");
            
            unlink($pngAbsoluteFilePath);
    }

    
    

}
?>