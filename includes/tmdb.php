<?php
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/settings.php';

class TMDB {
    private static $apiKey;
    private static $readToken;
    private static $baseUrl = 'https://api.themoviedb.org/3';
    private static $imageBase = 'https://image.tmdb.org/t/p';
    
    public static function init() {
        self::$apiKey = SiteSetting::get('tmdb_api_key', 'cb44223c5dee5676ed3a839f42ed27e3');
        self::$readToken = SiteSetting::get('tmdb_read_token', '');
    }
    
    private static function request($endpoint, $params = array()) {
        self::init();
        $params['api_key'] = self::$apiKey;
        $params['language'] = 'zh-CN';
        $url = self::$baseUrl . $endpoint . '?' . http_build_query($params);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!empty(self::$readToken)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . self::$readToken
            ));
        }
        $response = curl_exec($ch);
        curl_close($ch);
        
        return $response ? json_decode($response, true) : null;
    }
    
    public static function getImageUrl($path, $size = 'w500') {
        if (empty($path)) return '';
        return self::$imageBase . '/' . $size . $path;
    }
    
    // 获取热门电影
    public static function getPopularMovies($page = 1) {
        return self::request('/movie/popular', array('page' => $page));
    }
    
    // 获取热门剧集
    public static function getPopularTv($page = 1) {
        return self::request('/tv/popular', array('page' => $page));
    }
    
    // 获取正在播放的电影
    public static function getNowPlaying($page = 1) {
        return self::request('/movie/now_playing', array('page' => $page));
    }
    
    // 获取热门动漫 (带类型过滤)
    public static function getPopularAnime($page = 1) {
        return self::request('/discover/tv', array(
            'page' => $page,
            'with_genres' => '16',
            'sort_by' => 'popularity.desc'
        ));
    }
    
    // 综艺
    public static function getPopularVariety($page = 1) {
        return self::request('/discover/tv', array(
            'page' => $page,
            'with_genres' => '10764',
            'sort_by' => 'popularity.desc'
        ));
    }
    
    // 获取详情
    public static function getMovieDetail($id) {
        $result = self::request('/movie/' . $id, array(
            'append_to_response' => 'credits,videos,images,recommendations,similar'
        ));
        if ($result) {
            $result['media_type'] = 'movie';
        }
        return $result;
    }
    
    public static function getTvDetail($id) {
        $result = self::request('/tv/' . $id, array(
            'append_to_response' => 'credits,videos,images,recommendations,similar,aggregate_credits'
        ));
        if ($result) {
            $result['media_type'] = 'tv';
        }
        return $result;
    }
    
    // 获取季详情
    public static function getSeasonDetail($tvId, $seasonNumber) {
        return self::request('/tv/' . $tvId . '/season/' . $seasonNumber, array(
            'append_to_response' => 'videos,images'
        ));
    }
    
    // 搜索
    public static function search($query, $page = 1) {
        return self::request('/search/multi', array(
            'query' => $query,
            'page' => $page,
            'include_adult' => 'false'
        ));
    }
    
    // 按分类获取
    public static function discover($type, $params = array()) {
        $endpoint = $type === 'movie' ? '/discover/movie' : '/discover/tv';
        return self::request($endpoint, $params);
    }
    
    // 获取趋势
    public static function getTrending($mediaType = 'all', $timeWindow = 'day') {
        return self::request('/trending/' . $mediaType . '/' . $timeWindow);
    }
    
    // 获取分类列表
    public static function getGenres($type = 'movie') {
        return self::request('/genre/' . $type . '/list');
    }
    
    // 搜索播放源接口
    public static function searchSource($keyword) {
        $sources = self::getPlaySources();
        $results = array();
        foreach ($sources as $source) {
            $url = $source['url'] . '?wd=' . urlencode($keyword);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $resp = curl_exec($ch);
            curl_close($ch);
            if ($resp) {
                $data = json_decode($resp, true);
                if ($data && isset($data['list']) && count($data['list']) > 0) {
                    $results[$source['id']] = array(
                        'source_name' => $source['name'],
                        'source_id' => $source['id'],
                        'data' => $data
                    );
                }
            }
        }
        return $results;
    }
    
    public static function getPlaySources() {
        $db = Database::getInstance();
        return $db->fetchAll("SELECT * FROM play_sources WHERE status = 1 ORDER BY sort_order ASC, id ASC");
    }
}
?>
