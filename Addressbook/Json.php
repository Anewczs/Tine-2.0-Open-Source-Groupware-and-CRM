<?php
class Addressbook_Json
{
    public function deleteAddress($_contactIDs)
    {
        $contactIDs = Zend_Json::decode($_contactIDs);
        if(is_array($contactIDs)) {
            $addresses = new Addressbook_Addresses();
            $addresses->delete($contactIDs);
            $result = array('success'   => TRUE);
        } else {
            $result = array('success'   => FALSE);
        }
        
        return $result;
    }
    
    public function readAddress($_contactID)
    {
        $addresses = new Addressbook_Addresses();
        if($rows = $addresses->find($_contactID)) {
            $result['results'] = $rows->toArray();
        }
        
        return $result;
    }
	
    public function saveAddress($_contactID = NULL)
    {
        $input = new Zend_Filter_Input(Addressbook_Addresses::getFilter(), Addressbook_Addresses::getValidator(), $_POST);
        
        if ($input->isValid()) {
            $address = new Addressbook_Addresses();
            
            $data = $input->getUnescaped();
            if(isset($data['contact_bday'])) {
                $locale = Zend_Registry::get('locale');
                $dateFormat = $locale->getTranslationList('Dateformat');
                // convert bday back to yyyy-mm-dd
                try {
                    $date = new Zend_Date($data['contact_bday'], $dateFormat['long'], 'en');
                    $data['contact_bday'] = $date->toString('yyyy-MM-dd');
                } catch (Exception $e) {
                    unset($data['contact_bday']);
                }
            }
            
            if($_contactID > 0) {
                try {
                    $where = $address->getAdapter()->quoteInto('contact_id = ?', (int)$_contactID);
                    $address->update($data, $where);
                    $result = array('success'           => true,
                                    'welcomeMessage'    => 'Entry updated');
                } catch (Exception $e) {
                    $result = array('success'           => false,
                                    'errorMessage'      => $e->getMessage());
                }
            } else {
                try {
                    $address->insert($data);
                    $result = array('success'           => true,
                                    'welcomeMessage'    => 'Entry saved');
                } catch (Exception $e) {
                    $result = array('success'           => false,
                                    'errorMessage'      => $e->getMessage());
                }
            }
        } else {
            foreach($input->getMessages() as $fieldName => $errorMessages) {
                $errors[] = array('id'  => $fieldName,
                                  'msg' => $errorMessages[0]);
            }
            
            $result = array('success'           => false,
                            'errors'            => $errors,
                            'errorMessage'      => 'filter NOT ok');
        }
        
        return $result;
    }
    
    public function getData($_datatype, $start, $sort, $dir, $limit)
    {
        $result = array();
        
        switch($_datatype) {
            case 'address':
                $snomClasses = new Addressbook_Addresses();
                if($rows = $snomClasses->fetchAll(NULL, "$sort $dir", $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $snomClasses->getTotalCount();
                }
                
                break;
        }
        
        return $result;
    }
	
	public function getMainTree() 
	{
		$treeNode = new Egwbase_Ext_Treenode('Addressbook', 'overview', 'addressbook', 'Addressbook', FALSE);
		$treeNode->setIcon('apps/kaddressbook.png');
		$treeNode->cls = 'treemain';

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'myaddresses', 'My Addresses', TRUE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'internaladdresses', 'My Fellows', TRUE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'fellowsaddresses', 'Fellows Addresses', FALSE);
		$treeNode->addChildren($childNode);

		$childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'sharedaddresses', 'Shared Addresses', FALSE);
		$treeNode->addChildren($childNode);

		return $treeNode;
	}
}
?>