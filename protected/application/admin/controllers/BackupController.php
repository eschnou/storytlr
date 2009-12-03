<?php
/*
 *    Copyright 2008-2009 Laurent Eschenauer and Alard Weisscher
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *  
 */

class Admin_BackupController extends Admin_BaseController
{
    protected $_section = 'tools'; 		
	
	public function indexAction() {
		// Fetch the list of configured services for this user
		$table	= new Sources();
		$data   = new Data();
		$sources = $table->getSources();
		$mysources = array();
		if ($sources) foreach ($sources as $source) {
			$model = SourceModel::newInstance($source['service']);
			$model->setSource($source);
			$e = array();
			$e['prefix'] 		= $model->getServicePrefix();
			$e['name'] 			= $model->getServiceName();
			$e['description'] 	= $model->getServiceDescription();
			$e['account'] 		= $model->getAccountName();
			$e['id'] 			= $source['id'];
			$mysources[] = $e;
		}

		$this->view->sources = $mysources;
	}

	public function csvAction() {
		// Get the request parameters
		$id = $this->_getParam('id');

		// Get the requested source
		$sources = new Sources();
		if (!($source = $sources->getSource($id))) {
			throw new Stuffpress_Exception("Unknown source id $id");
		}

		// Are we the owner of the source ?
		if ($source['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_AccessDeniedException("Not the owner of this resource");
		}

		// Ok, prepare the download
		$data	= new Data();
		$items	= $data->getAllItems($source['id']);

		// This is not a layout or rendered page
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();

		// Send the appropriate headers
		$service 	= $source['service'];
		header("Content-Type: text/csv; charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"backup-$service.csv\"");

		// Open the output pipe
		$out = fopen('php://output', 'w');

		// Display the header
		$keys 		= array_keys($items[0]->getBackup());
		fputcsv($out, $keys);

		// Output the content
		for($i=0; $i<count($items); $i++) {
			fputcsv($out, $items[$i]->getBackup());
		}

		// Close the output pipe
		fclose($out);
		exit();
	}

	public function excelAction() {
		// Get the request parameters
		$id = $this->_getParam('id');

		// Get the requested source
		$sources = new Sources();
		if (!($source = $sources->getSource($id))) {
			throw new Stuffpress_Exception("Unknown source id $id");
		}

		// Are we the owner of the source
		if ($source['user_id'] != $this->_application->user->id) {
			throw new Stuffpress_Exception("You are not authorized to download this source");
		}

		// Get the source meta data
		$model = SourceModel::newInstance($source['service']);
		$model->setSource($source);
		$service= $model->getServiceName();
		$desc	= $model->getServiceDescription();
		
		// Get the actual data to backup
		$data	= new Data();
		$items	= $data->getAllItems($source['id']);

		// This is not a layout or rendered page
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		
		// Start the writer to Excel
		require_once('PHPExcel.php');
		require_once('PHPExcel/Writer/Excel2007.php');
		$objPHPExcel = new PHPExcel();

		// Set properties
		$objPHPExcel->getProperties()->setCreator("storytlr.com");
		$objPHPExcel->getProperties()->setLastModifiedBy("storytlr.com");
		$objPHPExcel->getProperties()->setTitle("Backup of {$this->_application->user->username}'s {$service} account");
		//$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
		$objPHPExcel->getProperties()->setDescription($desc);

		// Add some data
		$objPHPExcel->setActiveSheetIndex(0);
		
		// Write the column titles
		$keys 		= array_keys($items[0]->getBackup());
		for($i=0; $i<count($keys); $i++) {
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($i,1, $keys[$i]);			
		}
		
		// Write the data
		for($i=0; $i<count($items); $i++) {
			$item	= $items[$i]->getBackup();
			for($j=0; $j<count($keys); $j++) {
				$value = $item[$keys[$j]];
				if ($value) $objPHPExcel->getActiveSheet()->getCellByColumnAndRow($j, $i+2)->setValueExplicit("'$value", PHPExcel_Cell_DataType::TYPE_STRING);
			}
		}

		// Rename sheet
		$objPHPExcel->getActiveSheet()->setTitle($service);

		// Save Excel 2007 file
		$root 		= Zend_Registry::get("root");
		$key		= Stuffpress_Token::create(6);
		$file		= "{$service}-{$this->_application->user->username}-[{$key}].xls";
	    $path 		= $root . "/public/files/$file";
		$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
		$objWriter->save($path);
		$this->_redirect($this->_base."/files/$file");
	}
}