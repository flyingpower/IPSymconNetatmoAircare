<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

class NetatmoAircareSensor extends IPSModule
{
    use NetatmoAircareCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('product_type', '');
        $this->RegisterPropertyString('product_id', '');

        $this->RegisterPropertyBoolean('with_last_contact', false);
        $this->RegisterPropertyBoolean('with_last_measure', false);
        $this->RegisterPropertyBoolean('with_wifi_strength', false);
        $this->RegisterPropertyBoolean('with_absolute_humidity', false);
        $this->RegisterPropertyBoolean('with_dewpoint', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);
        $this->RegisterPropertyBoolean('with_minmax', false);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('Healthy'), 'Farbe' => 0x228B22];
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('Fine'), 'Farbe' => 0x90EE90];
        $associations[] = ['Wert' => 2, 'Name' => $this->Translate('Fair'), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 3, 'Name' => $this->Translate('Poor'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 4, 'Name' => $this->Translate('Unhealthy'), 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('NetatmoAircare.Index', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' =>  0, 'Name' => '%d', 'Farbe' => 0x008040];
        $associations[] = ['Wert' => 40, 'Name' => '%d', 'Farbe' => 0xFFFF31];
        $associations[] = ['Wert' => 65, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 95, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('NetatmoAircare.Noise', VARIABLETYPE_INTEGER, ' dB', 0, 130, 0, 1, 'Speaker', $associations);

        $associations = [];
        $associations[] = ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x008000];
        $associations[] = ['Wert' => 1000, 'Name' => '%d', 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 1250, 'Name' => '%d', 'Farbe' => 0xFF8000];
        $associations[] = ['Wert' => 1300, 'Name' => '%d', 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('NetatmoAircare.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->wifi_strength2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->wifi_strength2text(1), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 2, 'Name' => $this->wifi_strength2text(2), 'Farbe' => 0x32CD32];
        $this->CreateVarProfile('NetatmoAircare.WifiStrength', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations);

        $this->CreateVarProfile('NetatmoAircare.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('NetatmoAircare.Humidity', VARIABLETYPE_FLOAT, ' %', 0, 0, 0, 0, 'Drops');
        $this->CreateVarProfile('NetatmoAircare.absHumidity', VARIABLETYPE_FLOAT, ' g/m³', 10, 100, 0, 0, 'Drops');
        $this->CreateVarProfile('NetatmoAircare.Dewpoint', VARIABLETYPE_FLOAT, ' °C', 0, 30, 0, 0, 'Drops');
        $this->CreateVarProfile('NetatmoAircare.Heatindex', VARIABLETYPE_FLOAT, ' °C', 0, 100, 0, 0, 'Temperature');

        $this->ConnectParent('{070C93FD-9D19-D670-2C73-20104B87F034}');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
        $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
        $with_wifi_strength = $this->ReadPropertyBoolean('with_wifi_strength');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_minmax = $this->ReadPropertyBoolean('with_minmax');

        $vpos = 1;

        $this->MaintainVariable('Status', $this->Translate('State'), VARIABLETYPE_BOOLEAN, '~Alert.Reversed', $vpos++, true);
        $this->MaintainVariable('LastContact', $this->Translate('last transmission'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_contact);
        $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
        $this->MaintainVariable('WifiStrength', $this->Translate('Strength of wifi-signal'), VARIABLETYPE_INTEGER, 'NetatmoAircare.WifiStrength', $vpos++, $with_wifi_strength);

        $this->MaintainVariable('Index', $this->Translate('Air Quality Health Index'), VARIABLETYPE_INTEGER, 'NetatmoAircare.Index', $vpos++, true);
        $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, true);
        $this->MaintainVariable('TemperatureMax', $this->Translate('Today\'s temperature-maximum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMaxTimestamp', $this->Translate('Time of today\'s temperature-maximum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMin', $this->Translate('Today\'s temperature-minimum'), VARIABLETYPE_FLOAT, 'Netatmo.Temperatur', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMinTimestamp', $this->Translate('Time of today\'s temperature-minimum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
        $this->MaintainVariable('CO2', $this->Translate('CO2'), VARIABLETYPE_INTEGER, 'NetatmoAircare.CO2', $vpos++, true);
        $this->MaintainVariable('Humidity', $this->Translate('Humidity'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Humidity', $vpos++, true);
        $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), VARIABLETYPE_FLOAT, 'NetatmoAircare.absHumidity', $vpos++, $with_absolute_humidity);
        $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), VARIABLETYPE_FLOAT, 'Netatmo.Dewpoint', $vpos++, $with_dewpoint);
        $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'Netatmo.Heatindex', $vpos++, $with_heatindex);

        $product_id = $this->ReadPropertyString('product_id');
        $product_type = $this->ReadPropertyString('product_type');
        $product_info = $product_id . ' (' . $product_type . ')';
        $this->SetSummary($product_info);

        $this->SetStatus(IS_ACTIVE);
    }

    protected function GetFormElements()
    {
        $formElements = [];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'module_disable', 'caption' => 'Instance is disabled'];

        $product_type = $this->ReadPropertyString('product_type');
        switch ($product_type) {
            case 'NHC':
                $product_type_s = 'Netatmo Room air sensor';
                break;
            default:
                $product_type_s = 'Netatmo Aircare';
                break;
        }

        $formElements[] = ['type' => 'Label', 'caption' => $product_type_s];

        $items = [];
        $items[] = ['type' => 'ValidationTextBox', 'name' => 'product_type', 'caption' => 'Product-Type'];
        $items[] = ['type' => 'ValidationTextBox', 'name' => 'product_id', 'caption' => 'Product-ID'];
        $formElements[] = ['type' => 'ExpansionPanel', 'items' => $items, 'caption' => 'Basic configuration (don\'t change)'];

        $items = [];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_last_contact', 'caption' => 'last transmission to Netatmo'];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_last_measure', 'caption' => 'Measurement-Timestamp'];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_wifi_strength', 'caption' => 'Strength of wifi-signal'];
        $formElements[] = ['type' => 'ExpansionPanel', 'items' => $items, 'caption' => 'optional data'];

        $items = [];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_absolute_humidity', 'caption' => 'absolute Humidity'];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_dewpoint', 'caption' => 'Dewpoint'];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_heatindex', 'caption' => 'Heatindex'];
        $items[] = ['type' => 'CheckBox', 'name' => 'with_minmax', 'caption' => 'Min/Max of temperature'];
        $formElements[] = ['type' => 'ExpansionPanel', 'items' => $items, 'caption' => 'optional weather data'];

        return $formElements;
    }

    protected function GetFormActions()
    {
        $formActions = [];

        return $formActions;
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    public function ReceiveData($data)
    {
        if ($this->CheckStatus() == STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $jdata = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($jdata, true), 0);

        $product_id = $this->ReadPropertyString('product_id');

        if ($buf != '') {
            $jdata = json_decode($buf, true);
            $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

            $devices = $this->GetArrayElem($jdata, 'body.devices', '');
            if ($devices != '') {
                foreach ($devices as $device) {
                    if ($product_id != $device['_id']) {
                        continue;
                    }
                    $this->SendDebug(__FUNCTION__, 'decode device=' . print_r($device, true), 0);
                }
            }
        }

        $this->SetStatus(IS_ACTIVE);
    }

    // Wifi-Strength
    private function map_wifi_strength($strength)
    {
        if ($strength <= 56) {
            // "good"
            $val = 2;
        } elseif ($strength <= 71) {
            // "average"
            $val = 1;
        } else {
            // "bad"
            $val = 0;
        }

        return $val;
    }

    private function wifi_strength2text($strength)
    {
        $strength2txt = [
            'bad',
            'average',
            'good',
        ];

        if ($strength >= 0 && $strength < count($strength2txt)) {
            $txt = $this->Translate($strength2txt[$strength]);
        } else {
            $txt = '';
        }
        return $txt;
    }

    private function wifi_strength2icon($strength)
    {
        $strength2icon = [
            'wifi_low.png',
            'wifi_medium.png',
            'wifi_high.png',
        ];

        if ($strength >= 0 && $strength < count($strength2icon)) {
            $img = $strength2icon[$strength];
        } else {
            $img = '';
        }
        return $img;
    }

    public function WifiStrength2Icon(string $wifi_strength, bool $asPath)
    {
        $img = $this->wifi_strength2icon($wifi_strength);
        /*
        if ($img != false) {
            $hook = $this->ReadPropertyString('hook');
            $img = $hook . '/imgs/' . $img;
        }
         */
        return $img;
    }

    public function WifiStrength2Text(string $wifi_strength)
    {
        $txt = $this->wifi_strength2text($wifi_strength);
    }
}
