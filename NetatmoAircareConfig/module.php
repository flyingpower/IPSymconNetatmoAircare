<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class NetatmoAircareConfig extends IPSModule
{
    use NetatmoAircare\StubsCommonLib;
    use NetatmoAircareLocalLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('ImportCategoryID', 0);

        $this->RegisterAttributeString('UpdateInfo', '');

        $this->InstallVarProfiles(false);

        $this->ConnectParent('{070C93FD-9D19-D670-2C73-20104B87F034}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $propertyNames = ['ImportCategoryID'];
        $this->MaintainReferences($propertyNames);

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    private function buildEntry($guid, $product_type, $product_id, $product_name, $product_category)
    {
        $instID = 0;
        $instIDs = IPS_GetInstanceListByModuleID($guid);
        foreach ($instIDs as $id) {
            $prodID = IPS_GetProperty($id, 'product_id');
            if ($prodID == $product_id) {
                $instID = $id;
                break;
            }
        }

        $catID = $this->ReadPropertyInteger('ImportCategoryID');

        $entry = [
            'category'   => $this->Translate($product_category),
            'name'       => $product_name,
            'product_id' => $product_id,
            'instanceID' => $instID,
            'create'     => [
                'moduleID'       => $guid,
                'location'       => $this->GetConfiguratorLocation($catID),
                'info'           => $product_name,
                'configuration'  => [
                    'product_type' => $product_type,
                    'product_id'   => $product_id,
                ]
            ]
        ];

        return $entry;
    }

    private function getConfiguratorValues()
    {
        $entries = [];

        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return $entries;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent', 0);
            return $entries;
        }

        $SendData = ['DataID' => '{076043C4-997E-6AB3-9978-DA212D50A9F5}', 'Function' => 'LastData'];
        $data = $this->SendDataToParent(json_encode($SendData));
        $jdata = json_decode($data, true);

        $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

        if (is_array($jdata)) {
            if (isset($jdata['body']['devices'])) {
                $devices = $jdata['body']['devices'];
                foreach ($devices as $device) {
                    $this->SendDebug(__FUNCTION__, 'device=' . print_r($device, true), 0);
                    if (!isset($device['_id'])) {
                        continue;
                    }
                    $product_id = $device['_id'];
                    $product_name = $device['station_name'];
                    $product_type = $device['type'];
                    switch ($product_type) {
                        case 'NHC':
                            $guid = '{F3940032-CC4B-9E69-383A-6FFAD13C5438}';
                            $product_category = 'Room air sensor';
                            break;
                        default:
                            $guid = '';
                            break;
                    }
                    if ($guid == '') {
                        $this->SendDebug(__FUNCTION__, 'ignore camera ' . $camera['id'] . ': unsupported type ' . $camera['type']);
                        continue;
                    }

                    $entry = $this->buildEntry($guid, $product_type, $product_id, $product_name, $product_category);
                    $entries[] = $entry;
                }
            }
        }

        $modules = [
            [
                'category' => 'Room air sensor',
                'guid'     => '{F3940032-CC4B-9E69-383A-6FFAD13C5438}',
            ],
        ];
        foreach ($modules as $module) {
            $category = $this->Translate($module['category']);
            $instIDs = IPS_GetInstanceListByModuleID($module['guid']);
            foreach ($instIDs as $instID) {
                $fnd = false;
                foreach ($entries as $entry) {
                    if ($entry['instanceID'] == $instID) {
                        $fnd = true;
                        break;
                    }
                }
                if ($fnd) {
                    continue;
                }

                $product_name = IPS_GetName($instID);
                $home_name = '';
                $product_id = IPS_GetProperty($instID, 'product_id');

                $entry = [
                    'instanceID' => $instID,
                    'category'   => $category,
                    'home'       => $home_name,
                    'name'       => $product_name,
                    'product_id' => $product_id,
                ];
                $entries[] = $entry;
                $this->SendDebug(__FUNCTION__, 'missing entry=' . print_r($entry, true), 0);
            }
        }

        return $entries;
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Netatmo Aircare Configurator');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'SelectCategory',
            'name'    => 'ImportCategoryID',
            'caption' => 'category for products to be created'
        ];

        $entries = $this->getConfiguratorValues();
        $formElements[] = [
            'type'    => 'Configurator',
            'name'    => 'products',
            'caption' => 'Products',

            'rowCount' => count($entries),

            'add'    => false,
            'delete' => false,
            'sort'   => [
                'column'    => 'name',
                'direction' => 'ascending'
            ],
            'columns' => [
                [
                    'caption' => 'Category',
                    'name'    => 'category',
                    'width'   => '200px',
                ],
                [
                    'caption' => 'Name',
                    'name'    => 'name',
                    'width'   => 'auto'
                ],
                [
                    'caption' => 'Id',
                    'name'    => 'product_id',
                    'width'   => '200px'
                ]
            ],
            'values' => $entries,
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }
}
