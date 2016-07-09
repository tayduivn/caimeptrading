<?php
Class tireimportdetailController Extends baseController {
    public function index() {
        $this->view->setLayout('admin');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        $this->view->data['lib'] = $this->lib;
        $this->view->data['title'] = 'Bảng giá lốp xe';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : null;
            $order = isset($_POST['order']) ? $_POST['order'] : null;
            $page = isset($_POST['page']) ? $_POST['page'] : null;
            $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : null;
            $limit = isset($_POST['limit']) ? $_POST['limit'] : 18446744073709;
        }
        else{
            $order_by = $this->registry->router->order_by ? $this->registry->router->order_by : 'tire_brand_name';
            $order = $this->registry->router->order_by ? $this->registry->router->order_by : 'ASC, tire_size_number ASC';
            $page = $this->registry->router->page ? (int) $this->registry->router->page : 1;
            $keyword = "";
            $limit = 20;
        }

        
        $tire_brand_model = $this->model->get('tirebrandModel');
        $tire_size_model = $this->model->get('tiresizeModel');
        $tire_pattern_model = $this->model->get('tirepatternModel');

        $tire_brands = $tire_brand_model->getAllTire();
        $tire_sizes = $tire_size_model->getAllTire();
        $tire_patterns = $tire_pattern_model->getAllTire();

        $this->view->data['tire_brands'] = $tire_brands;
        $this->view->data['tire_sizes'] = $tire_sizes;
        $this->view->data['tire_patterns'] = $tire_patterns;

        $join = array('table'=>'tire_brand, tire_size, tire_pattern','where'=>'tire_brand.tire_brand_id = tire_import_detail.tire_brand AND tire_size.tire_size_id = tire_import_detail.tire_size AND tire_pattern.tire_pattern_id = tire_import_detail.tire_pattern');

        $tire_import_detail_model = $this->model->get('tireimportdetailModel');
        $sonews = $limit;
        $x = ($page-1) * $sonews;
        $pagination_stages = 2;
        
        $data = array(
            'where' => '1=1',
        );
        
        
        $tongsodong = count($tire_import_detail_model->getAllTire($data,$join));
        $tongsotrang = ceil($tongsodong / $sonews);
        

        $this->view->data['page'] = $page;
        $this->view->data['order_by'] = $order_by;
        $this->view->data['order'] = $order;
        $this->view->data['keyword'] = $keyword;
        $this->view->data['pagination_stages'] = $pagination_stages;
        $this->view->data['tongsotrang'] = $tongsotrang;
        $this->view->data['limit'] = $limit;
        $this->view->data['sonews'] = $sonews;

        $data = array(
            'order_by'=>$order_by,
            'order'=>$order,
            'limit'=>$x.','.$sonews,
            'where' => '1=1',
            );
        
      
        if ($keyword != '') {
            $search = '( tire_brand_name LIKE "%'.$keyword.'%" 
                OR tire_size_number LIKE "%'.$keyword.'%" )';
            
                $data['where'] = $data['where'].' AND '.$search;
        }

        

        
        $this->view->data['tire_imports'] = $tire_import_detail_model->getAllTire($data,$join);
        $this->view->data['lastID'] = isset($tire_import_detail_model->getLastTire()->tire_import_detail_id)?$tire_import_detail_model->getLastTire()->tire_import_detail_id:0;

        /* Lấy tổng doanh thu*/
        
        /*************/
        $this->view->show('tireimportdetail/index');
    }

   
    public function add(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if (isset($_POST['yes'])) {
            
            $tire_import_detail_model = $this->model->get('tireimportdetailModel');
            $data = array(
                        
                        'tire_brand' => trim($_POST['tire_brand']),
                        'tire_size' => trim($_POST['tire_size']),
                        'tire_pattern' => trim($_POST['tire_pattern']),
                        'tire_price' => trim(str_replace(',','',$_POST['tire_price'])),
                        'code' => trim($_POST['code']),
                        );
            if ($_POST['yes'] != "") {
                
                    $tire_import_detail_model->updateTire($data,array('tire_import_detail_id' => trim($_POST['yes'])));
                    echo "Cập nhật thành công";

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."edit"."|".$_POST['yes']."|tire_import_detail|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
            else{
                
                    $tire_import_detail_model->createTire($data);

                    
                    echo "Thêm thành công";

                 

                    date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."add"."|".$tire_import_detail_model->getLastTire()->tire_import_detail_id."|tire_import_detail|".implode("-",$data)."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                
                
            }
                    
        }
    }

    public function delete(){
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tire_import_detail_model = $this->model->get('tireimportdetailModel');
           
            if (isset($_POST['xoa'])) {
                $data = explode(',', $_POST['xoa']);
                foreach ($data as $data) {
                       $tire_import_detail_model->deleteTire($data);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$data."|tire_import_detail|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
                    
                }
                return true;
            }
            else{
                        $tire_import_detail_model->deleteTire($_POST['data']);
                        echo "Xóa thành công";
                        date_default_timezone_set("Asia/Ho_Chi_Minh"); 
                        $filename = "action_logs.txt";
                        $text = date('d/m/Y H:i:s')."|".$_SESSION['user_logined']."|"."delete"."|".$_POST['data']."|tire_import_detail|"."\n"."\r\n";
                        
                        $fh = fopen($filename, "a") or die("Could not open log file.");
                        fwrite($fh, $text) or die("Could not write file!");
                        fclose($fh);
                    
            }
            
        }
    }

    public function import(){
        $this->view->disableLayout();
        header('Content-Type: text/html; charset=utf-8');
        if (!isset($_SESSION['userid_logined'])) {
            return $this->view->redirect('user/login');
        }
        if ($_SESSION['role_logined'] != 1 && $_SESSION['role_logined'] != 3 && $_SESSION['role_logined'] != 8) {
            return $this->view->redirect('user/login');
        }
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['import']['name'] != null) {

            require("lib/Classes/PHPExcel/IOFactory.php");
            require("lib/Classes/PHPExcel.php");

            $tireimportdetail = $this->model->get('tireimportdetailModel');
            $tirebrand = $this->model->get('tirebrandModel');
            $tiresize = $this->model->get('tiresizeModel');
            $tirepattern = $this->model->get('tirepatternModel');

            $objPHPExcel = new PHPExcel();
            // Set properties
            if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xls") {
                $objReader = PHPExcel_IOFactory::createReader('Excel5');
            }
            else if (pathinfo($_FILES['import']['name'], PATHINFO_EXTENSION) == "xlsx") {
                $objReader = PHPExcel_IOFactory::createReader('Excel2007');
            }
            
            $objReader->setReadDataOnly(false);

            $objPHPExcel = $objReader->load($_FILES['import']['tmp_name']);
            $objWorksheet = $objPHPExcel->getActiveSheet();

            

            $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
            $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'

            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5

            //var_dump($objWorksheet->getMergeCells());die();
            
            $cell_code = $objWorksheet->getCellByColumnAndRow(0, 1);
            $code = $cell_code->getCalculatedValue();

                for ($row = 3; $row <= $highestRow; ++ $row) {
                    $val = array();
                    for ($col = 0; $col < $highestColumnIndex; ++ $col) {
                        $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                        // Check if cell is merged
                        foreach ($objWorksheet->getMergeCells() as $cells) {
                            if ($cell->isInRange($cells)) {
                                $currMergedCellsArray = PHPExcel_Cell::splitRange($cells);
                                $cell = $objWorksheet->getCell($currMergedCellsArray[0][0]);
                                break;
                                
                            }
                        }
                        //$val[] = $cell->getValue();
                        //$val[] = is_numeric($cell->getCalculatedValue()) ? round($cell->getCalculatedValue()) : $cell->getCalculatedValue();
                        $val[] = $cell->getCalculatedValue();
                        //here's my prob..
                        //echo $val;
                    }
                    if ($val[1] != null && $val[2] != null && $val[3] != null && $val[4] != null) {

                            if($tirebrand->getTireByWhere(array('tire_brand_name'=>trim($val[1])))) {
                                $id_brand = $tirebrand->getTireByWhere(array('tire_brand_name'=>trim($val[1])))->tire_brand_id;
                            }
                            else if(!$tirebrand->getTireByWhere(array('tire_brand_name'=>trim($val[1])))){
                                $tirebrand_data = array(
                                    'tire_brand_name' => trim($val[1]),
                                    );
                                $tirebrand->createTire($tirebrand_data);

                                $id_brand = $tirebrand->getLastTire()->tire_brand_id;
                            }

                            if($tiresize->getTireByWhere(array('tire_size_number'=>trim($val[2])))) {
                                $id_size = $tiresize->getTireByWhere(array('tire_size_number'=>trim($val[2])))->tire_size_id;
                            }
                            else if(!$tiresize->getTireByWhere(array('tire_size_number'=>trim($val[2])))){
                                $tiresize_data = array(
                                    'tire_size_number' => trim($val[2]),
                                    );
                                $tiresize->createTire($tiresize_data);

                                $id_size = $tiresize->getLastTire()->tire_size_id;
                            }

                            if($tirepattern->getTireByWhere(array('tire_pattern_name'=>trim($val[3])))) {
                                $id_pattern = $tirepattern->getTireByWhere(array('tire_pattern_name'=>trim($val[3])))->tire_pattern_id;
                            }
                            else if(!$tirepattern->getTireByWhere(array('tire_pattern_name'=>trim($val[3])))){
                                $tirepattern_data = array(
                                    'tire_pattern_name' => trim($val[3]),
                                    );
                                $tirepattern->createTire($tirepattern_data);

                                $id_pattern = $tirepattern->getLastTire()->tire_pattern_id;
                            }


                            if ($id_brand != null && $id_size != null && $id_pattern != null) {
                                
                                if($tireimportdetail->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern,'code'=>$code))) {
                                    $id_tire_import_detail = $tireimportdetail->getTireByWhere(array('tire_brand'=>$id_brand,'tire_size'=>$id_size,'tire_pattern'=>$id_pattern,'code'=>$code))->tire_import_detail_id;

                                    $tire_import_detail_data = array(
                                    'tire_brand' => $id_brand,
                                    'tire_size' => $id_size,
                                    'tire_pattern' => $id_pattern,
                                    'tire_price' => trim($val[4]),
                                    'code' => $code,
                                    
                                    );
                                    $tireimportdetail->updateTire($tire_import_detail_data,array('tire_import_detail_id' => $id_tire_import_detail));
                                }
                                else{
                                    $tire_import_detail_data = array(
                                    'tire_brand' => $id_brand,
                                    'tire_size' => $id_size,
                                    'tire_pattern' => $id_pattern,
                                    'tire_price' => trim($val[4]),
                                    'code' => $code,
                                    
                                    );
                                    $tireimportdetail->createTire($tire_import_detail_data);
                                }
                            }
                        
                    }
                    
                    //var_dump($this->getNameDistrict($this->lib->stripUnicode($val[1])));
                    // insert


                }
                //return $this->view->redirect('transport');
            
            return $this->view->redirect('tireimportdetail');
        }
        $this->view->show('tireimportdetail/import');

    }
    

}
?>