<?php
declare(strict_types=1);


namespace OrangeShadow\ElasticSearch;


class UrlParser
{
    /**
     * Дробим урл по фильтру
     * @param string $url
     * @param true $ajax
     * @return array
     */
    public static function convertUrlToElasticParam(string $url='', $ajax = false): array
    {

        $result = array();
        $smartParts = explode("/", $url);

        foreach ($smartParts as $smartPart) {

            $smartPart = preg_split("/-(from|to|is|or)-/", $smartPart, -1, PREG_SPLIT_DELIM_CAPTURE);
            $slug = $smartPart[0];
//            if($ajax && !in_array($slug,['gender','adult','category'])) {
//                continue;
//            }
            if (in_array('from', $smartPart, true) && isset($smartPart[2])) {
                $result[ $slug . '_from' ] = (int)$smartPart[2];
                if(isset($smartPart[4])) {
                    $result[ $slug . '_to' ] = (int)$smartPart[4];
                }
            } elseif (in_array('to', $smartPart, true)) {
                $result[ $slug . '_to' ] = isset($smartPart[4]) ? (int)$smartPart[4] : (int)$smartPart[2];
            } elseif (in_array('is', $smartPart, true)) {
                $result[ $slug ] = array_values(array_filter(array_slice($smartPart, 2), function ($item) {
                    return $item !== 'or';
                }));
            }
        }
        $result = array_map(function ($item) {
            if (is_array($item) && count($item) === 1) {
                return $item[0];
            }

            return $item;
        }, $result);

        return $result;
    }

    /**
     * @param string | null $smartFilterPath
     * @param string | null $sectionCodePath
     * @return array
     */
    public static function getElasticParamsFromRequest(?string $smartFilterPath = null, ?string $sectionCodePath = null): array
    {
        if (!empty($sectionCodePath)) {
            $sections = explode('/', $sectionCodePath);
        } else if (!empty($_REQUEST['SECTION_CODE_PATH'])) {
            $sections = explode('/', $_REQUEST['SECTION_CODE_PATH']);
        }

        $result = $_REQUEST;

        if (isset($_REQUEST['ajax'])) {
            $result = array_filter($result,
                function ($item) {
                    return $item !== 'on';
                });
            unset($result['ajax']);
            $resultFilter = self::convertUrlToElasticParam($result['SMART_FILTER_PATH']?:'');
            if ($resultFilter['age_group']==='adult' && isset($resultFilter['gender']))  {
                $result['gender'] = $resultFilter['gender'];
                $result['age_group'] =[$resultFilter['age_group']];
            }
        } else if(!empty($smartFilterPath)) {
            $result = self::convertUrlToElasticParam($smartFilterPath?:'');
        } else {
            $result = self::convertUrlToElasticParam($result['SMART_FILTER_PATH']?:'');
        }


        if (isset($result['SECTION_CODE_PATH'])) {
            unset($result['SECTION_CODE_PATH']);
        }

        if (isset($result['SMART_FILTER_PATH'])) {
            unset($result['SMART_FILTER_PATH']);
        }

        if (empty($result['category']) && count($sections) >= 2 && $_REQUEST["ajax"] != "y") {
            $result['category'] = array_pop($sections);
        }

        if (!empty($sections)) {
            $result['section'] = array_shift($sections);
        }

        $remove = [];
        foreach ($result as $key => $item) {
            if (isset($result[ $key . '_max' ]) && (int)$result[ $key . '_max' ] === (int)$result[ $key ]) {
                $remove[] = $key;
                $remove[] = $key . '_max';
            }

            if (isset($result[ $key . '_min' ])
                && (int)$result[ $key . '_min' ] === (int)$result[ $key ]
                && (int)$result[ str_replace('_from', '_to', $key) . '_max' ] === (int)$result[ str_replace('_from', '_to', $key) ]
            ) {
                $remove[] = $key;
                $remove[] = $key . '_min';
            }
        }

        foreach ($remove as $key) {
            unset($result[ $key ]);
        }

        //Костыль
        if (isset($result['collection'])) {
            $result['collection'] = str_replace("'", '_', $result['collection']);
        }

        $result['foto'] = 'Y';

        return $result;
    }
}
