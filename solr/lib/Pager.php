<?php
//dengjing@vip.qq.com
class Pager {
    public static $pageSize = 30;
    public static $numberLink = 5;//当前页的前后显示多少个数字链接
    public static $pagerExt = true;//是否显示总记录数 总页数

    public static function requestPage($total) {
        if ($total == 0) return 1;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] >= 1 ? abs(intval($_GET['page'])) : 1;
        if ($page > ceil($total / self::$pageSize)) $page = ceil($total / self::$pageSize);
        return $page;
    }
    
    public static function requestSegmentPage($total) {
        if ($total == 0) return 1;
        $requestPage = Url::segment('page');        
        $page = !is_null($requestPage) && is_numeric($requestPage) && $requestPage >= 1 ? abs(intval($requestPage)) : 1;             
        if ($page > ceil($total / self::$pageSize)) $page = ceil($total / self::$pageSize);        
        return $page;        
    }

    public static function limit($page) {
        $page = abs(intval($page));
        return ($page - 1) * Pager::$pageSize . "," . Pager::$pageSize;
    }
    public static function showPage($total, $pageSize = null) {
        $pageSize = is_null($pageSize) ? self::$pageSize : $pageSize;
        $result = $otherParmas = '';        
        $queryString = $_SERVER['QUERY_STRING'];        
        $currPage = 1;
        $pageMax = ceil($total / $pageSize) > 0 ? ceil($total / $pageSize) : '1';
        if (!empty($queryString)) {
            parse_str($queryString, $params);
            $currPage = isset($params['page']) && is_numeric($params['page']) && $params['page'] >= 1 ? abs($params['page']) : 1;
            if (isset($params['page'])) unset($params['page']);
            foreach ($params as $key => $value) {
                if (!empty($value)) $otherParmas .= "{$key}={$value}&";
            }
        }
        if ($currPage < 1) $currPage = 1;
        if ($currPage > $pageMax) $currPage = $pageMax;
        $firstLink = $currPage == 1 ? "<span style=\"padding:3px 6px;\">1</span>" : "<a class=\"button-blue\" href=\"?{$otherParmas}page=1\">1</a> ";
        $prevPage = $currPage == 1 ? 1 : $currPage - 1;
        $prevLink = $currPage == 1 ? null : "<a class=\"button-blue\" href=\"?{$otherParmas}page={$prevPage}\">上一页</a> ";
        $numberLink = $lastLink = null;
        if ($currPage - self::$numberLink > 2) $numberLink .= "<span style=\"padding:3px 6px;\">...</span>";
        for ($i = $currPage - self::$numberLink; $i <= $currPage + self::$numberLink; $i++) {            
            if ($i > 1 && $i< $pageMax) {
                if ($i == $currPage) $numberLink .= "<span style=\"padding:3px 6px;\">{$i}</span> ";
                else $numberLink .= "<a class=\"button-blue\" href=\"?{$otherParmas}page={$i}\">{$i}</a> ";
            } 
        }
        if ($currPage + self::$numberLink < $pageMax - 1) $numberLink .= "<span>...</span>";
        $nextPage = $currPage < $pageMax ? $currPage + 1 : $pageMax;
        $nextLink = $currPage == $pageMax ? null : "<a class=\"button-blue\" href=\"?{$otherParmas}page={$nextPage}\">下一页</a> ";
        if ($pageMax != 1) {
            $lastLink = $currPage == $pageMax ? "<span style=\"padding:3px 6px;\">{$pageMax}</span>" : "<a class=\"button-blue\" href=\"?{$otherParmas}page={$pageMax}\">{$pageMax}</a> ";
        }        
        $ext = self::$pagerExt == true ? "当前 <span class=\"orangeRed\">{$currPage}</span>/<span class=\"orange\">{$pageMax}</span>页 {$pageSize}条/页 共 <span class=\"royalBlue\">{$total}</span> 条记录" : null;
        //$result .= $firstLink . $prevLink . $nextLink . $lastLink . $ext;
        $result .= $prevLink . $firstLink . $numberLink . $lastLink . $nextLink . $ext;
        return $result;
    }
    
    public static function showSegmentPage($total, $pageSize = null) {
        self::$pagerExt = false;
        $pageSize = is_null($pageSize) ? self::$pageSize : $pageSize;
        $result = '';
        $queryString = !empty($_SERVER['QUERY_STRING']) ? "?{$_SERVER['QUERY_STRING']}" : null;
        $currPage = 1;
        $pageMax = ceil($total / $pageSize) > 0 ? ceil($total / $pageSize) : '1';
        if (!is_null($segmentPage = Url::segment('page'))) $currPage = $segmentPage;
        $segmentArray = Url::segmentsArray();
        if ((bool)($pageIndex = array_search('page', $segmentArray))) {
            unset($segmentArray[$pageIndex]);
            if (isset($segmentArray[$pageIndex + 1])) unset($segmentArray[$pageIndex + 1]);
        }
        $segmentArray[] = 'page';
        $fullUrl = Url::siteUrl(implode('/', $segmentArray) . '/');
        if ($currPage < 1) $currPage = 1;
        if ($currPage > $pageMax) $currPage = $pageMax;
        $firstLink = $currPage == 1 ? "<span>{$currPage}</span> " : "<a href=\"" . str_replace('/page/', '', $fullUrl) . "{$queryString}\" title=\"首页\">1</a> ";
        $prevPage = $currPage == 1 ? 1 : $currPage - 1;
        
        if ($currPage == 1) {
            $prevLink = null;
        } elseif ($currPage == 2) {
            $prevLink = "<a href=\"" . str_replace('/page/', '', $fullUrl) . "{$queryString}\" title=\"上一页\">&lt;</a> ";
        } else {
            $prevLink = "<a href=\"{$fullUrl}{$prevPage}{$queryString}\" title=\"上一页\">&lt;</a> ";
        }
//        $prevLink = $currPage == 1 ? null : "<a href=\"{$fullUrl}{$prevPage}{$queryString}\" title=\"上一页\">&lt;</a> ";
        $numberLink = $lastLink = null;
        if ($currPage - self::$numberLink > 2) $numberLink .= "<span class=\"dots\">...</span>";
        for ($i = $currPage - self::$numberLink; $i <= $currPage + self::$numberLink; $i++) {            
            if ($i > 1 && $i< $pageMax) {
                if ($i == $currPage) $numberLink .= "<span>{$i}</span> ";
                else $numberLink .= "<a href=\"{$fullUrl}{$i}{$queryString}\" title=\"第{$i}页\">{$i}</a> ";
            } 
        }
        if ($currPage + self::$numberLink < $pageMax - 1) $numberLink .= "<span class=\"dots\">...</span>";
        $nextPage = $currPage < $pageMax ? $currPage + 1 : $pageMax;
        $nextLink = $currPage == $pageMax ? null : "<a href=\"{$fullUrl}{$nextPage}{$queryString}\" title=\"下一页\">&gt;</a> ";
        if ($pageMax != 1) {
            $lastLink = $currPage == $pageMax ? "<span>{$pageMax}</span>" : "<a href=\"{$fullUrl}{$pageMax}{$queryString}\" title=\"末页\">{$pageMax}</a> ";
        }        
        $ext = self::$pagerExt == true ? "当前 <span class=\"orangeRed\">{$currPage}</span>/<span class=\"orange\">{$pageMax}</span>页 {$pageSize}条/页 共 <span class=\"royalBlue\">{$total}</span> 条记录" : null;
        //$result .= $firstLink . $prevLink . $nextLink . $lastLink . $ext;
        $result .= $prevLink . $firstLink . $numberLink . $lastLink . $nextLink;
        return $result;
    }    

    public static function getQueryString($except = null, $questionMark = false, $andMark = false) {
        $queryString = $_SERVER['QUERY_STRING'];
        $filterParams = '';
        if (!empty($queryString)) {
            parse_str($queryString, $params);
            if (is_array($except)) {
                foreach ($except as $value) {
                    if (isset($params[$value])) unset($params[$value]);
                }
            }
            if (is_string($except)) {
                if (isset($params[$except])) unset($params[$except]);
            }
            foreach ($params as $key => $value) {
                if (strval($value) != '') $filterParams .= "{$key}={$value}&";
            }
            $filterParams = $filterParams != '' ? substr($filterParams, 0, -1) : '';
			if ($andMark && $filterParams != '') {				
				switch ($andMark) {
					case 'left': $filterParams = "&{$filterParams}";break;
					case 'right': $filterParams = "{$filterParams}&";break;
					default: break;
				}
			}
			$filterParams = $questionMark && $filterParams != '' ? '?' . $filterParams : $filterParams;			
			
        }
        return $filterParams;
    }
}
?>
