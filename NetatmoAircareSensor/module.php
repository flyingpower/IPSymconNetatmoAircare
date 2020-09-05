<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/local.php';   // lokale Funktionen

class NetatmoAircareSensor extends IPSModule
{
    use NetatmoAircareCommonLib;
    use NetatmoAircareLocalLib;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('product_type', '');
        $this->RegisterPropertyString('product_id', '');

        $this->RegisterPropertyBoolean('with_last_contact', false);
        $this->RegisterPropertyBoolean('with_last_measure', false);
        $this->RegisterPropertyBoolean('with_wifi_strength', false);
        $this->RegisterPropertyBoolean('with_absolute_pressure', false);
        $this->RegisterPropertyBoolean('with_absolute_humidity', false);
        $this->RegisterPropertyBoolean('with_dewpoint', false);
        $this->RegisterPropertyBoolean('with_heatindex', false);
        $this->RegisterPropertyBoolean('with_minmax', false);
        $this->RegisterPropertyInteger('minutes2fail', 30);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->Translate('Healthy'), 'Farbe' => 0x88A4C9];	// blau
        $associations[] = ['Wert' => 1, 'Name' => $this->Translate('Fine'), 'Farbe' => 0x68C29F];		// grün
        $associations[] = ['Wert' => 2, 'Name' => $this->Translate('Fair'), 'Farbe' => 0xF5E552];		// gelb
        $associations[] = ['Wert' => 3, 'Name' => $this->Translate('Poor'), 'Farbe' => 0xF6C57C];		// orange
        $associations[] = ['Wert' => 4, 'Name' => $this->Translate('Unhealthy'), 'Farbe' => 0xED7071];	// rot
        $this->CreateVarProfile('NetatmoAircare.Index', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, '', $associations);

        $associations = [];
        $associations[] = ['Wert' =>  0, 'Name' => '%d', 'Farbe' => 0x88A4C9];
        $associations[] = ['Wert' => 56, 'Name' => '%d', 'Farbe' => 0x68C29F];
        $associations[] = ['Wert' => 65, 'Name' => '%d', 'Farbe' => 0xF5E552];
        $associations[] = ['Wert' => 70, 'Name' => '%d', 'Farbe' => 0xF6C57C];
        $associations[] = ['Wert' => 80, 'Name' => '%d', 'Farbe' => 0xED7071];
        $this->CreateVarProfile('NetatmoAircare.Noise', VARIABLETYPE_INTEGER, ' dB', 0, 130, 0, 1, 'Speaker', $associations);

        $associations = [];
        $associations[] = ['Wert' =>    0, 'Name' => '%d', 'Farbe' => 0x88A4C9];
        $associations[] = ['Wert' =>  900, 'Name' => '%d', 'Farbe' => 0x68C29F];
        $associations[] = ['Wert' => 1150, 'Name' => '%d', 'Farbe' => 0xF5E552];
        $associations[] = ['Wert' => 1400, 'Name' => '%d', 'Farbe' => 0xF6C57C];
        $associations[] = ['Wert' => 1600, 'Name' => '%d', 'Farbe' => 0xED7071];
        $this->CreateVarProfile('NetatmoAircare.CO2', VARIABLETYPE_INTEGER, ' ppm', 250, 2000, 0, 1, 'Gauge', $associations);

        $associations = [];
        $associations[] = ['Wert' => 0, 'Name' => $this->wifi_strength2text(0), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => 1, 'Name' => $this->wifi_strength2text(1), 'Farbe' => 0xFFFF00];
        $associations[] = ['Wert' => 2, 'Name' => $this->wifi_strength2text(2), 'Farbe' => 0x32CD32];
        $this->CreateVarProfile('NetatmoAircare.WifiStrength', VARIABLETYPE_INTEGER, '', 0, 0, 0, 1, 'Intensity', $associations);

        $this->CreateVarProfile('NetatmoAircare.Temperatur', VARIABLETYPE_FLOAT, ' °C', -10, 30, 0, 1, 'Temperature');
        $this->CreateVarProfile('NetatmoAircare.Pressure', VARIABLETYPE_FLOAT, ' mbar', 500, 1200, 0, 0, 'Gauge');
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
        $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
        $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
        $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
        $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
        $with_minmax = $this->ReadPropertyBoolean('with_minmax');

        $vpos = 1;

        $this->MaintainVariable('Status', $this->Translate('State'), VARIABLETYPE_BOOLEAN, '~Alert.Reversed', $vpos++, true);
        $this->MaintainVariable('LastContact', $this->Translate('last transmission'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_contact);
        $this->MaintainVariable('LastMeasure', $this->Translate('last measurement'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_last_measure);
        $this->MaintainVariable('WifiStrength', $this->Translate('Strength of wifi-signal'), VARIABLETYPE_INTEGER, 'NetatmoAircare.WifiStrength', $vpos++, $with_wifi_strength);

        $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, true);
        $this->MaintainVariable('TemperatureMax', $this->Translate('Today\'s temperature-maximum'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMaxTimestamp', $this->Translate('Time of today\'s temperature-maximum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMin', $this->Translate('Today\'s temperature-minimum'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Temperatur', $vpos++, $with_minmax);
        $this->MaintainVariable('TemperatureMinTimestamp', $this->Translate('Time of today\'s temperature-minimum'), VARIABLETYPE_INTEGER, '~UnixTimestampTime', $vpos++, $with_minmax);
        $this->MaintainVariable('Humidity', $this->Translate('Humidity'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Humidity', $vpos++, true);
        $this->MaintainVariable('AbsoluteHumidity', $this->Translate('absolute humidity'), VARIABLETYPE_FLOAT, 'NetatmoAircare.absHumidity', $vpos++, $with_absolute_humidity);
        $this->MaintainVariable('Dewpoint', $this->Translate('Dewpoint'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Dewpoint', $vpos++, $with_dewpoint);
        $this->MaintainVariable('Heatindex', $this->Translate('Heatindex'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Heatindex', $vpos++, $with_heatindex);
        $this->MaintainVariable('CO2', $this->Translate('CO2'), VARIABLETYPE_INTEGER, 'NetatmoAircare.CO2', $vpos++, true);
        $this->MaintainVariable('Noise', $this->Translate('Noise'), VARIABLETYPE_INTEGER, 'NetatmoAircare.Noise', $vpos++, true);
        $this->MaintainVariable('Pressure', $this->Translate('Air pressure'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Pressure', $vpos++, true);
        $this->MaintainVariable('AbsolutePressure', $this->Translate('absolute pressure'), VARIABLETYPE_FLOAT, 'NetatmoAircare.Pressure', $vpos++, $with_absolute_pressure);
        $this->MaintainVariable('Index', $this->Translate('Air Quality Health Index'), VARIABLETYPE_INTEGER, 'NetatmoAircare.Index', $vpos++, true);

        $product_id = $this->ReadPropertyString('product_id');
        $product_type = $this->ReadPropertyString('product_type');
        $product_info = $product_id . ' (' . $product_type . ')';
        $this->SetSummary($product_info);

        $this->SetStatus(IS_ACTIVE);
    }

    private function GetFormElements()
    {
        $formElements = [];

        if ($this->HasActiveParent() == false) {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => 'Instance has no active parent instance',
            ];
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Instance is disabled'
        ];

        $product_type = $this->ReadPropertyString('product_type');
        switch ($product_type) {
            case 'NHC':
                $product_type_s = 'Netatmo Room air sensor';
                break;
            default:
                $product_type_s = 'Netatmo Aircare';
                break;
        }

        $formElements[] = [
            'type'    => 'Label',
            'caption' => $product_type_s];

        $items = [];
        $items[] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'product_type',
            'caption' => 'Product-Type'
        ];
        $items[] = [
            'type'    => 'ValidationTextBox',
            'name'    => 'product_id',
            'caption' => 'Product-ID'
        ];
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => $items,
            'caption' => 'Basic configuration (don\'t change)'
        ];

        $items = [];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_last_contact',
            'caption' => 'last transmission to Netatmo'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_last_measure',
            'caption' => 'Measurement-Timestamp'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_wifi_strength',
            'caption' => 'Strength of wifi-signal'
        ];
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => $items,
            'caption' => 'optional data'
        ];

        $items = [];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_absolute_pressure',
            'caption' => 'absolute pressure'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_absolute_humidity',
            'caption' => 'absolute humidity'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_dewpoint',
            'caption' => 'Dewpoint'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_heatindex',
            'caption' => 'Heatindex'
        ];
        $items[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_minmax',
            'caption' => 'Min/Max of temperature'
        ];
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => $items,
            'caption' => 'optional weather data'
        ];

        $items = [];
        $items[] = [
            'type'    => 'Label',
            'caption' => 'Duration until the connection to netatmo or between stations is marked disturbed'
        ];
        $items[] = [
            'type'    => 'NumberSpinner',
            'name'    => 'minutes2fail',
            'caption' => 'Minutes'
        ];
        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => $items,
            'caption' => 'Processing information'
        ];

        return $formElements;
    }

    private function GetFormActions()
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
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $now = time();

        $product_id = $this->ReadPropertyString('product_id');

        if ($data != '') {
            $jdata = json_decode($data, true);
            $this->SendDebug(__FUNCTION__, 'jdata=' . print_r($jdata, true), 0);

            $buf = $this->GetArrayElem($jdata, 'Buffer', '');
            $this->SendDebug(__FUNCTION__, 'buf=' . print_r($buf, true), 0);
            $jbuf = json_decode($buf, true);
            $this->SendDebug(__FUNCTION__, 'jbuf=' . print_r($jbuf, true), 0);

            $body = $this->GetArrayElem($jbuf, 'body', '');
            $devices = $this->GetArrayElem($jbuf, 'body.devices', '');
            $this->SendDebug(__FUNCTION__, 'devices=' . print_r($devices, true), 0);
            if ($devices != '') {
                foreach ($devices as $device) {
                    if ($product_id != $device['_id']) {
                        continue;
                    }
                    $this->SendDebug(__FUNCTION__, 'decode device=' . print_r($device, true), 0);

                    $minutes2fail = $this->ReadPropertyInteger('minutes2fail');
                    $with_last_contact = $this->ReadPropertyBoolean('with_last_contact');
                    $with_last_measure = $this->ReadPropertyBoolean('with_last_measure');
                    $with_wifi_strength = $this->ReadPropertyBoolean('with_wifi_strength');
                    $with_absolute_pressure = $this->ReadPropertyBoolean('with_absolute_pressure');
                    $with_absolute_humidity = $this->ReadPropertyBoolean('with_absolute_humidity');
                    $with_dewpoint = $this->ReadPropertyBoolean('with_dewpoint');
                    $with_heatindex = $this->ReadPropertyBoolean('with_heatindex');
                    $with_minmax = $this->ReadPropertyBoolean('with_minmax');

                    $station_status = true;
                    $last_contact = $device['last_status_store'];
                    if (is_int($last_contact)) {
                        $sec = $now - $last_contact;
                        $min = floor($sec / 60);
                        if ($min > $minutes2fail) {
                            $station_status = false;
                        }
                    } else {
                        $last_contact = 0;
                    }

                    $this->SetValue('Status', $station_status);

                    if ($with_last_contact) {
                        $this->SetValue('LastContact', $last_contact);
                    }

                    if ($with_wifi_strength) {
                        $wifi_status = $this->map_wifi_strength($device['wifi_status']);
                        $this->SetValue('WifiStrength', $wifi_status);
                    }
                    if ($with_last_measure) {
                        $last_measure = $this->GetArrayElem($device, 'dashboard_data.time_utc', 0);
                        $this->SetValue('LastMeasure', $last_measure);
                    }

                    $Temperature = $this->GetArrayElem($device, 'dashboard_data.Temperature', 0);
                    $CO2 = $this->GetArrayElem($device, 'dashboard_data.CO2', 0);
                    $Humidity = $this->GetArrayElem($device, 'dashboard_data.Humidity', 0);
                    $Noise = $this->GetArrayElem($device, 'dashboard_data.Noise', 0);
                    $Pressure = $this->GetArrayElem($device, 'dashboard_data.Pressure', 0);

                    $AbsolutePressure = $this->GetArrayElem($device, 'dashboard_data.AbsolutePressure', 0);
                    $health_idx = $this->GetArrayElem($device, 'dashboard_data.health_idx', 0);
                    $min_temp = $this->GetArrayElem($device, 'dashboard_data.min_temp', 0);
                    $max_temp = $this->GetArrayElem($device, 'dashboard_data.max_temp', 0);
                    $date_max_temp = $this->GetArrayElem($device, 'dashboard_data.date_max_temp', 0);
                    $date_min_temp = $this->GetArrayElem($device, 'dashboard_data.date_min_temp', 0);

                    $this->SetValue('Temperature', $Temperature);
                    if ($with_minmax) {
                        $this->SetValue('TemperatureMax', $max_temp);
                        $this->SetValue('TemperatureMaxTimestamp', $date_max_temp);
                        $this->SetValue('TemperatureMin', $min_temp);
                        $this->SetValue('TemperatureMinTimestamp', $date_min_temp);
                    }
                    $this->SetValue('Humidity', $Humidity);
                    if ($with_absolute_humidity) {
                        $abs_humidity = $this->CalcAbsoluteHumidity($Temperature, $Humidity);
                        $this->SetValue('AbsoluteHumidity', $abs_humidity);
                    }
                    if ($with_dewpoint) {
                        $dewpoint = $this->CalcDewpoint($Temperature, $Humidity);
                        $this->SetValue('Dewpoint', $dewpoint);
                    }
                    if ($with_heatindex) {
                        $heatindex = $this->CalcHeatindex($Temperature, $Humidity);
                        $this->SetValue('Heatindex', $heatindex);
                    }
                    $this->SetValue('CO2', $CO2);
                    $this->SetValue('Noise', $Noise);
                    $this->SetValue('Pressure', $Pressure);
                    if ($with_absolute_pressure) {
                        $this->SetValue('AbsolutePressure', $AbsolutePressure);
                    }
                    $this->SetValue('Index', $health_idx);
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

    // Taupunkt berechnen
    //   Quelle: https://www.wetterochs.de/wetter/feuchte.html
    public function CalcDewpoint(float $temp, float $humidity)
    {
        if ($temp > 0) {
            $k2 = 17.62;
            $k3 = 243.12;
        } else {
            $k2 = 22.46;
            $k3 = 272.62;
        }
        $dewpoint = $k3 * (($k2 * $temp) / ($k3 + $temp) + log($humidity / 100));
        $dewpoint = $dewpoint / (($k2 * $k3) / ($k3 + $temp) - log($humidity / 100));
        $dewpoint = round($dewpoint, 0);
        return $dewpoint;
    }

    // relative Luffeuchtigkeit in absolute Feuchte umrechnen
    //   Quelle: https://www.wetterochs.de/wetter/feuchte.html
    public function CalcAbsoluteHumidity(float $temp, float $humidity)
    {
        if ($temp >= 0) {
            $a = 7.5;
            $b = 237.3;
        } else {
            $a = 7.6;
            $b = 240.7;
        }

        $R = 8314.3; // universelle Gaskonstante in J/(kmol*K)
        $mw = 18.016; // Molekulargewicht des Wasserdampfes in kg/kmol

        // Sättigungsdamphdruck in hPa
        $SDD = 6.1078 * pow(10, (($a * $temp) / ($b + $temp)));

        // Dampfdruck in hPa
        $DD = $humidity / 100 * $SDD;

        $v = log10($DD / 6.1078);

        // Taupunkttemperatur in °C
        $TD = $b * $v / ($a - $v);

        // Temperatur in Kelvin
        $TK = $temp + 273.15;

        // absolute Feuchte in g Wasserdampf pro m³ Luft
        $AF = pow(10, 5) * $mw / $R * $DD / $TK;
        $AF = round($AF * 10) / 10; // auf eine NK runden

        return $AF;
    }

    // Temperatur als Heatindex umrechnen
    //   Quelle: https://de.wikipedia.org/wiki/Hitzeindex
    public function CalcHeatindex(float $temp, float $hum)
    {
        if ($temp < 27 || $hum < 40) {
            return $temp;
        }
        $c1 = -8.784695;
        $c2 = 1.61139411;
        $c3 = 2.338549;
        $c4 = -0.14611605;
        $c5 = -1.2308094 * pow(10, -2);
        $c6 = -1.6424828 * pow(10, -2);
        $c7 = 2.211732 * pow(10, -3);
        $c8 = 7.2546 * pow(10, -4);
        $c9 = -3.582 * pow(10, -6);

        $hi = $c1
            + $c2 * $temp
            + $c3 * $hum
            + $c4 * $temp * $hum
            + $c5 * pow($temp, 2)
            + $c6 * pow($hum, 2)
            + $c7 * pow($temp, 2) * $hum
            + $c8 * $temp * pow($hum, 2)
            + $c9 * pow($temp, 2) * pow($hum, 2);
        $hi = round($hi); // ohne NK
        return $hi;
    }
}
