<?php

declare(strict_types=1);

namespace Crawlora\Fotmob;

class CrawloraException extends \RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null, public readonly ?string $operationId = null, public readonly ?string $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

class ClientException extends CrawloraException {}
class ServerException extends CrawloraException {}
class NetworkException extends CrawloraException {}

final class Client
{
    private static array $operations;
    private bool $closed = false;
    private string $apiKey;
    private string $baseUrl;
    private float $timeout;
    private ?\Closure $transport;

    public const PLATFORM = 'fotmob';
    public const VERSION = '0.1.8';
    public const OPERATION_COUNT = 31;
    public const OPERATION_IDS = ["fotmob-audio-matches", "fotmob-fifa-ranking-periods", "fotmob-fifa-rankings", "fotmob-latest-news", "fotmob-league", "fotmob-leagues", "fotmob-lineup-builder-players", "fotmob-lineup-builder-team", "fotmob-match", "fotmob-match-media", "fotmob-matches", "fotmob-news", "fotmob-news-article", "fotmob-player", "fotmob-player-match-stats", "fotmob-player-matches", "fotmob-player-stats", "fotmob-search", "fotmob-seasons", "fotmob-stats", "fotmob-stats-categories", "fotmob-table", "fotmob-team", "fotmob-team-fixtures", "fotmob-team-news", "fotmob-transfers", "fotmob-trending-news", "fotmob-trending-searches", "fotmob-tv-guide", "fotmob-tv-guide-channels", "fotmob-tv-guide-countries"];

    public function __construct(?string $apiKey = null, string $baseUrl = 'https://api.crawlora.net/api/v1', float $timeout = 30.0, ?callable $transport = null)
    {
        $this->apiKey = $apiKey ?? (getenv('CRAWLORA_API_KEY') ?: '');
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->transport = $transport === null ? null : \Closure::fromCallable($transport);
        self::$operations ??= json_decode(<<<'JSON'
{"fotmob-audio-matches": {"id": "fotmob-audio-matches", "method": "GET", "params": [], "path": "/fotmob/audio-matches", "pathParams": [], "produces": ["application/json"], "queryParams": [], "security": ["ApiKeyAuth"]}, "fotmob-fifa-ranking-periods": {"id": "fotmob-fifa-ranking-periods", "method": "GET", "params": [{"description": "Ranking gender", "enum": ["men", "women"], "in": "query", "name": "gender", "required": true, "type": "string", "x-example": "men"}], "path": "/fotmob/fifa-ranking-periods", "pathParams": [], "produces": ["application/json"], "queryParams": [{"enum": ["men", "women"], "in": "query", "name": "gender", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-fifa-rankings": {"id": "fotmob-fifa-rankings", "method": "GET", "params": [{"description": "Ranking gender", "enum": ["men", "women"], "in": "query", "name": "gender", "required": true, "type": "string", "x-example": "men"}, {"description": "Period id returned for this gender by /fotmob/fifa-ranking-periods", "in": "query", "name": "period_id", "required": true, "type": "string", "x-example": "20260720"}], "path": "/fotmob/fifa-rankings", "pathParams": [], "produces": ["application/json"], "queryParams": [{"enum": ["men", "women"], "in": "query", "name": "gender", "required": true, "type": "string"}, {"in": "query", "name": "period_id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-latest-news": {"id": "fotmob-latest-news", "method": "GET", "params": [{"description": "Zero-based news offset; defaults to 0", "in": "query", "maximum": 10000, "minimum": 0, "name": "start_index", "type": "integer"}], "path": "/fotmob/latest-news", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "start_index", "type": "integer"}], "security": ["ApiKeyAuth"]}, "fotmob-league": {"id": "fotmob-league", "method": "GET", "params": [{"description": "Numeric FotMob league id from /fotmob/leagues", "in": "query", "name": "league_id", "required": true, "type": "integer", "x-example": 47}, {"description": "Optional season value from /fotmob/seasons for this league", "in": "query", "name": "season", "type": "string", "x-example": "2025/2026"}, {"default": false, "description": "Include the optional overview shot map; increases response size", "in": "query", "name": "shotmap", "type": "boolean"}], "path": "/fotmob/league", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "league_id", "required": true, "type": "integer"}, {"in": "query", "name": "season", "type": "string"}, {"in": "query", "name": "shotmap", "type": "boolean"}], "security": ["ApiKeyAuth"]}, "fotmob-leagues": {"id": "fotmob-leagues", "method": "GET", "params": [], "path": "/fotmob/leagues", "pathParams": [], "produces": ["application/json"], "queryParams": [], "security": ["ApiKeyAuth"]}, "fotmob-lineup-builder-players": {"id": "fotmob-lineup-builder-players", "method": "GET", "params": [{"description": "Comma-separated list of 1 to 11 numeric FotMob player ids", "in": "query", "name": "player_ids", "required": true, "type": "string", "x-example": "961995,562727"}], "path": "/fotmob/lineup-builder-players", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "player_ids", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-lineup-builder-team": {"id": "fotmob-lineup-builder-team", "method": "GET", "params": [{"description": "Numeric FotMob team id discoverable through /fotmob/search", "in": "query", "name": "team_id", "required": true, "type": "string", "x-example": "9825"}], "path": "/fotmob/lineup-builder-team", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "team_id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-match": {"id": "fotmob-match", "method": "GET", "params": [{"description": "Numeric FotMob match id", "in": "query", "name": "id", "required": true, "type": "string", "x-example": "5181825"}], "path": "/fotmob/match", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-match-media": {"id": "fotmob-match-media", "method": "GET", "params": [{"description": "Numeric FotMob match id, discoverable from /fotmob/matches or /fotmob/search", "in": "query", "name": "id", "required": true, "type": "string", "x-example": "5795457"}], "path": "/fotmob/match-media", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-matches": {"id": "fotmob-matches", "method": "GET", "params": [{"description": "Date in YYYYMMDD format", "in": "query", "name": "date", "required": true, "type": "string", "x-example": "20260925"}, {"description": "IANA timezone; defaults to UTC", "in": "query", "name": "timezone", "type": "string", "x-example": "Asia/Shanghai"}], "path": "/fotmob/matches", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "date", "required": true, "type": "string"}, {"in": "query", "name": "timezone", "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-news": {"id": "fotmob-news", "method": "GET", "params": [{"description": "Numeric FotMob league id", "in": "query", "name": "league_id", "required": true, "type": "string", "x-example": "47"}, {"description": "Zero-based news offset; defaults to 0", "in": "query", "maximum": 10000, "minimum": 0, "name": "start_index", "type": "integer"}], "path": "/fotmob/news", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "league_id", "required": true, "type": "string"}, {"in": "query", "name": "start_index", "type": "integer"}], "security": ["ApiKeyAuth"]}, "fotmob-news-article": {"id": "fotmob-news-article", "method": "GET", "params": [{"description": "Complete FotMob top-news article id and slug from the public article URL", "in": "query", "name": "id", "required": true, "type": "string", "x-example": "29776-fotmob-totw-premier-league-matchday-5s-best-xi"}], "path": "/fotmob/news-article", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-player": {"id": "fotmob-player", "method": "GET", "params": [{"description": "Numeric FotMob player id from fotmob/search", "in": "query", "name": "id", "required": true, "type": "string", "x-example": "961995"}, {"default": true, "description": "Include market-value history; defaults to true", "in": "query", "name": "include_market_values", "type": "boolean"}], "path": "/fotmob/player", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "required": true, "type": "string"}, {"in": "query", "name": "include_market_values", "type": "boolean"}], "security": ["ApiKeyAuth"]}, "fotmob-player-match-stats": {"id": "fotmob-player-match-stats", "method": "GET", "params": [{"description": "Numeric FotMob player id", "in": "query", "name": "player_id", "required": true, "type": "string", "x-example": "961995"}, {"description": "Numeric FotMob match id", "in": "query", "name": "match_id", "required": true, "type": "string", "x-example": "5795457"}], "path": "/fotmob/player-match-stats", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "player_id", "required": true, "type": "string"}, {"in": "query", "name": "match_id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-player-matches": {"id": "fotmob-player-matches", "method": "GET", "params": [{"description": "Numeric FotMob player id from fotmob/search", "in": "query", "name": "player_id", "required": true, "type": "string", "x-example": "961995"}, {"default": "all", "description": "all, or a numeric league id from fotmob/player matchFilters", "in": "query", "name": "league_id", "type": "string", "x-example": "47"}, {"default": "all", "description": "all, or a numeric team id paired with league_id in fotmob/player matchFilters", "in": "query", "name": "team_id", "type": "string", "x-example": "9825"}, {"description": "Numeric before timestamp from the upstream previous URL; omit for the newest page", "in": "query", "name": "before", "type": "string", "x-example": "1767902400"}], "path": "/fotmob/player-matches", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "player_id", "required": true, "type": "string"}, {"in": "query", "name": "league_id", "type": "string"}, {"in": "query", "name": "team_id", "type": "string"}, {"in": "query", "name": "before", "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-player-stats": {"id": "fotmob-player-stats", "method": "GET", "params": [{"description": "Numeric FotMob player id from fotmob/search", "in": "query", "name": "player_id", "required": true, "type": "string", "x-example": "961995"}, {"description": "Player-specific entryId from fotmob/player statSeasons", "in": "query", "name": "season_id", "required": true, "type": "string", "x-example": "0-1"}], "path": "/fotmob/player-stats", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "player_id", "required": true, "type": "string"}, {"in": "query", "name": "season_id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-search": {"id": "fotmob-search", "method": "GET", "params": [{"description": "Search phrase, 1 to 50 characters", "in": "query", "maxLength": 50, "minLength": 1, "name": "term", "required": true, "type": "string", "x-example": "Arsenal"}], "path": "/fotmob/search", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "term", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-seasons": {"id": "fotmob-seasons", "method": "GET", "params": [{"description": "Numeric FotMob league id from /fotmob/leagues", "in": "query", "name": "league_id", "required": true, "type": "integer", "x-example": 47}], "path": "/fotmob/seasons", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "league_id", "required": true, "type": "integer"}], "security": ["ApiKeyAuth"]}, "fotmob-stats": {"id": "fotmob-stats", "method": "GET", "params": [{"description": "Numeric FotMob league id", "in": "query", "name": "league_id", "required": true, "type": "string", "x-example": "47"}, {"description": "Numeric season id from /fotmob/stats-categories; defaults to the newest season", "in": "query", "name": "season_id", "type": "string", "x-example": "36781"}, {"description": "Stats subject", "enum": ["players", "teams"], "in": "query", "name": "type", "required": true, "type": "string", "x-example": "players"}, {"description": "Stat id returned by /fotmob/stats-categories for this league, season, and type", "in": "query", "name": "stat", "required": true, "type": "string", "x-example": "goals"}, {"description": "Optional numeric team id to filter player stats", "in": "query", "name": "team_id", "type": "string", "x-example": "8456"}, {"description": "Optional client-side player position filter", "enum": ["all", "striker", "winger", "attackingMidfielder", "midfielder", "fullback", "centerBack"], "in": "query", "name": "position", "type": "string", "x-example": "striker"}], "path": "/fotmob/stats", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "league_id", "required": true, "type": "string"}, {"in": "query", "name": "season_id", "type": "string"}, {"enum": ["players", "teams"], "in": "query", "name": "type", "required": true, "type": "string"}, {"in": "query", "name": "stat", "required": true, "type": "string"}, {"in": "query", "name": "team_id", "type": "string"}, {"enum": ["all", "striker", "winger", "attackingMidfielder", "midfielder", "fullback", "centerBack"], "in": "query", "name": "position", "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-stats-categories": {"id": "fotmob-stats-categories", "method": "GET", "params": [{"description": "Numeric FotMob league id", "in": "query", "name": "league_id", "required": true, "type": "string", "x-example": "47"}, {"description": "Numeric season id; omit to discover seasons", "in": "query", "name": "season_id", "type": "string", "x-example": "36781"}, {"description": "Stats subject", "enum": ["players", "teams"], "in": "query", "name": "type", "required": true, "type": "string", "x-example": "players"}], "path": "/fotmob/stats-categories", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "league_id", "required": true, "type": "string"}, {"in": "query", "name": "season_id", "type": "string"}, {"enum": ["players", "teams"], "in": "query", "name": "type", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-table": {"id": "fotmob-table", "method": "GET", "params": [{"description": "Numeric FotMob league id", "in": "query", "name": "league_id", "required": true, "type": "string", "x-example": "47"}], "path": "/fotmob/table", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "league_id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-team": {"id": "fotmob-team", "method": "GET", "params": [{"description": "Numeric FotMob team id", "in": "query", "name": "id", "required": true, "type": "string", "x-example": "9825"}], "path": "/fotmob/team", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "id", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-team-fixtures": {"id": "fotmob-team-fixtures", "method": "GET", "params": [{"description": "Numeric FotMob team id", "in": "query", "name": "team_id", "required": true, "type": "string", "x-example": "9825"}, {"description": "Opaque cursor copied from fixtures.previousFixturesUrl in /fotmob/team or previous in the preceding response", "in": "query", "name": "cursor", "required": true, "type": "string", "x-example": "https://pub.fotmob.com/prod/db/api/team/9825/fixture-by-date?beforeTimestamp=1785607200"}], "path": "/fotmob/team-fixtures", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "team_id", "required": true, "type": "string"}, {"in": "query", "name": "cursor", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-team-news": {"id": "fotmob-team-news", "method": "GET", "params": [{"description": "Numeric FotMob team id", "in": "query", "name": "team_id", "required": true, "type": "integer", "x-example": 8456}, {"description": "Zero-based news offset from 0 through 10000", "in": "query", "maximum": 10000, "minimum": 0, "name": "start_index", "type": "integer", "x-example": 0}], "path": "/fotmob/team-news", "pathParams": [], "produces": ["application/json"], "queryParams": [{"in": "query", "name": "team_id", "required": true, "type": "integer"}, {"in": "query", "name": "start_index", "type": "integer"}], "security": ["ApiKeyAuth"]}, "fotmob-transfers": {"id": "fotmob-transfers", "method": "GET", "params": [{"default": "all", "description": "Feed mode", "enum": ["all", "rumours", "popular"], "in": "query", "name": "mode", "type": "string"}, {"default": 1, "description": "One-based result page; 50 rows per page", "in": "query", "maximum": 200, "minimum": 1, "name": "page", "type": "integer"}, {"default": "6months", "description": "Time window", "enum": ["6months", "1year", "2years", "3years"], "in": "query", "name": "last", "type": "string"}, {"default": "all", "description": "Transfer direction; applied when league_ids or team_ids is supplied", "enum": ["all", "in", "out"], "in": "query", "name": "direction", "type": "string"}, {"description": "Minimum transfer fee in EUR", "in": "query", "minimum": 0, "name": "min_fee", "type": "integer"}, {"description": "Maximum transfer fee in EUR", "in": "query", "minimum": 0, "name": "max_fee", "type": "integer"}, {"description": "Comma-separated numeric FotMob league ids, up to 50; discover with /fotmob/leagues", "in": "query", "name": "league_ids", "type": "string", "x-example": "47"}, {"description": "Comma-separated numeric FotMob team ids, up to 50; discover with /fotmob/search", "in": "query", "name": "team_ids", "type": "string", "x-example": "8456"}, {"default": "lastModified", "description": "Sort column", "enum": ["lastModified", "fee", "date", "name", "fromClubName", "toClubName"], "in": "query", "name": "order_by", "type": "string"}, {"default": false, "description": "Exclude contract-extension records", "in": "query", "name": "exclude_extensions", "type": "boolean"}, {"default": false, "description": "Return only likely rumours; mode must be rumours", "in": "query", "name": "likely_only", "type": "boolean"}], "path": "/fotmob/transfers", "pathParams": [], "produces": ["application/json"], "queryParams": [{"enum": ["all", "rumours", "popular"], "in": "query", "name": "mode", "type": "string"}, {"in": "query", "name": "page", "type": "integer"}, {"enum": ["6months", "1year", "2years", "3years"], "in": "query", "name": "last", "type": "string"}, {"enum": ["all", "in", "out"], "in": "query", "name": "direction", "type": "string"}, {"in": "query", "name": "min_fee", "type": "integer"}, {"in": "query", "name": "max_fee", "type": "integer"}, {"in": "query", "name": "league_ids", "type": "string"}, {"in": "query", "name": "team_ids", "type": "string"}, {"enum": ["lastModified", "fee", "date", "name", "fromClubName", "toClubName"], "in": "query", "name": "order_by", "type": "string"}, {"in": "query", "name": "exclude_extensions", "type": "boolean"}, {"in": "query", "name": "likely_only", "type": "boolean"}], "security": ["ApiKeyAuth"]}, "fotmob-trending-news": {"id": "fotmob-trending-news", "method": "GET", "params": [], "path": "/fotmob/trending-news", "pathParams": [], "produces": ["application/json"], "queryParams": [], "security": ["ApiKeyAuth"]}, "fotmob-trending-searches": {"id": "fotmob-trending-searches", "method": "GET", "params": [], "path": "/fotmob/trending-searches", "pathParams": [], "produces": ["application/json"], "queryParams": [], "security": ["ApiKeyAuth"]}, "fotmob-tv-guide": {"id": "fotmob-tv-guide", "method": "GET", "params": [{"description": "Market code from /fotmob/tv-guide-countries", "enum": ["us", "se", "gb", "de", "no", "es", "mx", "ar", "bo", "cl", "co", "cr", "ec", "gt", "hn", "ni", "pa", "py", "pe", "uy", "ve", "da", "ca", "au", "at", "be", "bg", "hr", "cy", "cz", "ee", "fi", "fr", "gr", "hu", "is", "ie", "il", "it", "nl", "pl", "pt", "ro", "ru", "ch", "tr", "za", "br", "in", "me", "id", "th", "mm", "al", "az", "bl", "ba", "ks", "la", "li", "mk", "rs", "sk", "ua", "essv", "nz", "bd", "cn", "gh", "hk", "jp", "kr", "ma", "mt", "my", "ng", "ph", "pk", "sg", "si", "tz"], "in": "query", "name": "country", "required": true, "type": "string", "x-example": "us"}, {"description": "IANA timezone for local times", "in": "query", "name": "timezone", "type": "string", "x-example": "America/New_York"}], "path": "/fotmob/tv-guide", "pathParams": [], "produces": ["application/json"], "queryParams": [{"enum": ["us", "se", "gb", "de", "no", "es", "mx", "ar", "bo", "cl", "co", "cr", "ec", "gt", "hn", "ni", "pa", "py", "pe", "uy", "ve", "da", "ca", "au", "at", "be", "bg", "hr", "cy", "cz", "ee", "fi", "fr", "gr", "hu", "is", "ie", "il", "it", "nl", "pl", "pt", "ro", "ru", "ch", "tr", "za", "br", "in", "me", "id", "th", "mm", "al", "az", "bl", "ba", "ks", "la", "li", "mk", "rs", "sk", "ua", "essv", "nz", "bd", "cn", "gh", "hk", "jp", "kr", "ma", "mt", "my", "ng", "ph", "pk", "sg", "si", "tz"], "in": "query", "name": "country", "required": true, "type": "string"}, {"in": "query", "name": "timezone", "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-tv-guide-channels": {"id": "fotmob-tv-guide-channels", "method": "GET", "params": [{"description": "Market code from /fotmob/tv-guide-countries", "enum": ["us", "se", "gb", "de", "no", "es", "mx", "ar", "bo", "cl", "co", "cr", "ec", "gt", "hn", "ni", "pa", "py", "pe", "uy", "ve", "da", "ca", "au", "at", "be", "bg", "hr", "cy", "cz", "ee", "fi", "fr", "gr", "hu", "is", "ie", "il", "it", "nl", "pl", "pt", "ro", "ru", "ch", "tr", "za", "br", "in", "me", "id", "th", "mm", "al", "az", "bl", "ba", "ks", "la", "li", "mk", "rs", "sk", "ua", "essv", "nz", "bd", "cn", "gh", "hk", "jp", "kr", "ma", "mt", "my", "ng", "ph", "pk", "sg", "si", "tz"], "in": "query", "name": "country", "required": true, "type": "string", "x-example": "us"}], "path": "/fotmob/tv-guide-channels", "pathParams": [], "produces": ["application/json"], "queryParams": [{"enum": ["us", "se", "gb", "de", "no", "es", "mx", "ar", "bo", "cl", "co", "cr", "ec", "gt", "hn", "ni", "pa", "py", "pe", "uy", "ve", "da", "ca", "au", "at", "be", "bg", "hr", "cy", "cz", "ee", "fi", "fr", "gr", "hu", "is", "ie", "il", "it", "nl", "pl", "pt", "ro", "ru", "ch", "tr", "za", "br", "in", "me", "id", "th", "mm", "al", "az", "bl", "ba", "ks", "la", "li", "mk", "rs", "sk", "ua", "essv", "nz", "bd", "cn", "gh", "hk", "jp", "kr", "ma", "mt", "my", "ng", "ph", "pk", "sg", "si", "tz"], "in": "query", "name": "country", "required": true, "type": "string"}], "security": ["ApiKeyAuth"]}, "fotmob-tv-guide-countries": {"id": "fotmob-tv-guide-countries", "method": "GET", "params": [], "path": "/fotmob/tv-guide-countries", "pathParams": [], "produces": ["application/json"], "queryParams": [], "security": ["ApiKeyAuth"]}}
JSON, true, 512, JSON_THROW_ON_ERROR);
    }

    public function request(string $operationId, array $params = [], string $responseType = 'auto'): mixed
    {
        if ($this->closed) {
            throw new ClientException('Client is closed', null, $operationId);
        }
        $operation = self::$operations[$operationId] ?? null;
        if ($operation === null) {
            throw new ClientException('Unknown operation: ' . $operationId, null, $operationId);
        }
        if ($this->apiKey === '') {
            throw new ClientException('Crawlora API key is required', null, $operationId);
        }
        $url = $this->buildUrl($operation, $params);
        $headers = [
            'x-api-key: ' . $this->apiKey,
            'User-Agent: crawlora-fotmob-php/0.1.8',
            'Accept: ' . (in_array('text/plain', $operation['produces'], true) ? 'application/json, text/plain' : 'application/json'),
        ];
        try {
            [$status, $contentType, $body] = $this->send($url, $headers, $operationId);
        } catch (CrawloraException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new NetworkException('Crawlora request failed: ' . $exception->getMessage(), null, $operationId, null, $exception);
        }
        if ($status < 200 || $status >= 300) {
            $class = $status >= 500 ? ServerException::class : ClientException::class;
            throw new $class('Crawlora returned HTTP ' . $status, $status, $operationId, $body);
        }
        return $this->parseResponse($body, $contentType, $operation, $params, $responseType);
    }

    public function close(): void
    {
        $this->closed = true;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function operationCount(): int
    {
        return self::OPERATION_COUNT;
    }

    public function operationIds(): array
    {
        return self::OPERATION_IDS;
    }

    public function operations(): array
    {
        return self::$operations;
    }

    public function audio_matches(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-audio-matches", $params, $responseType);
    }
    public function fifa_ranking_periods(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-fifa-ranking-periods", $params, $responseType);
    }
    public function fifa_rankings(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-fifa-rankings", $params, $responseType);
    }
    public function latest_news(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-latest-news", $params, $responseType);
    }
    public function league(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-league", $params, $responseType);
    }
    public function leagues(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-leagues", $params, $responseType);
    }
    public function lineup_builder_players(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-lineup-builder-players", $params, $responseType);
    }
    public function lineup_builder_team(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-lineup-builder-team", $params, $responseType);
    }
    public function match(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-match", $params, $responseType);
    }
    public function match_media(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-match-media", $params, $responseType);
    }
    public function matches(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-matches", $params, $responseType);
    }
    public function news(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-news", $params, $responseType);
    }
    public function news_article(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-news-article", $params, $responseType);
    }
    public function player(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-player", $params, $responseType);
    }
    public function player_match_stats(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-player-match-stats", $params, $responseType);
    }
    public function player_matches(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-player-matches", $params, $responseType);
    }
    public function player_stats(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-player-stats", $params, $responseType);
    }
    public function search(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-search", $params, $responseType);
    }
    public function seasons(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-seasons", $params, $responseType);
    }
    public function stats(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-stats", $params, $responseType);
    }
    public function stats_categories(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-stats-categories", $params, $responseType);
    }
    public function table(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-table", $params, $responseType);
    }
    public function team(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-team", $params, $responseType);
    }
    public function team_fixtures(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-team-fixtures", $params, $responseType);
    }
    public function team_news(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-team-news", $params, $responseType);
    }
    public function transfers(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-transfers", $params, $responseType);
    }
    public function trending_news(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-trending-news", $params, $responseType);
    }
    public function trending_searches(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-trending-searches", $params, $responseType);
    }
    public function tv_guide(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-tv-guide", $params, $responseType);
    }
    public function tv_guide_channels(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-tv-guide-channels", $params, $responseType);
    }
    public function tv_guide_countries(mixed ...$params): mixed
    {
        $responseType = $params['_response_type'] ?? $params['response_type'] ?? 'auto';
        unset($params['_response_type'], $params['response_type']);
        return $this->request("fotmob-tv-guide-countries", $params, $responseType);
    }

    private function buildUrl(array $operation, array $params): string
    {
        $known = array_column($operation['params'], 'name');
        $unknown = array_diff(array_keys($params), $known, ['response_type', '_response_type']);
        if ($unknown !== []) {
            throw new ClientException('Unknown parameters: ' . implode(', ', $unknown), null, $operation['id']);
        }
        $path = $operation['path'];
        foreach ($operation['params'] as $param) {
            if ($param['in'] !== 'path') {
                continue;
            }
            $name = $param['name'];
            if (!array_key_exists($name, $params) || $params[$name] === null) {
                throw new ClientException('Missing path parameter: ' . $name, null, $operation['id']);
            }
            $path = str_replace('{' . $name . '}', rawurlencode((string) $params[$name]), $path);
        }
        $pairs = [];
        foreach ($operation['queryParams'] as $param) {
            $name = $param['name'];
            $value = $params[$name] ?? ($param['default'] ?? null);
            if ($value === null) {
                if ($param['required'] ?? false) {
                    throw new ClientException('Missing query parameter: ' . $name, null, $operation['id']);
                }
                continue;
            }
            $enumValues = $param['enum'] ?? ($param['items']['enum'] ?? null);
            $values = is_array($value) ? $value : [$value];
            $invalidEnum = false;
            foreach ($values as $item) {
                if ($enumValues !== null && !in_array((string) $item, array_map('strval', $enumValues), true)) {
                    $invalidEnum = true;
                    break;
                }
            }
            if ($invalidEnum) {
                throw new ClientException('Invalid value for ' . $name, null, $operation['id']);
            }
            if (is_array($value)) {
                $format = $param['collectionFormat'] ?? 'csv';
                if ($format === 'multi') {
                    foreach ($value as $item) {
                        $pairs[] = [rawurlencode($name), rawurlencode($this->stringify($item))];
                    }
                } else {
                    $separator = ['csv' => ',', 'ssv' => ' ', 'tsv' => "\t", 'pipes' => '|'][$format] ?? ',';
                    $pairs[] = [rawurlencode($name), rawurlencode(implode($separator, array_map([$this, 'stringify'], $value)))];
                }
            } else {
                $pairs[] = [rawurlencode($name), rawurlencode($this->stringify($value))];
            }
        }
        $query = implode('&', array_map(static fn(array $pair): string => $pair[0] . '=' . $pair[1], $pairs));
        return $this->baseUrl . $path . ($query === '' ? '' : '?' . $query);
    }

    private function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }
        return (string) $value;
    }

    private function send(string $url, array $headers, string $operationId): array
    {
        if ($this->transport !== null) {
            $result = ($this->transport)($url, $headers, $this->timeout);
            return [(int) $result['status'], (string) ($result['content_type'] ?? ''), (string) ($result['body'] ?? '')];
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new NetworkException('Could not initialize cURL', null, $operationId);
        }
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($this->timeout * 1000),
        ]);
        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new NetworkException('Crawlora request failed: ' . $message, null, $operationId);
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $contentType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);
        return [$status, $contentType, (string) $body];
    }

    private function parseResponse(string $body, string $contentType, array $operation, array $params, string $responseType): mixed
    {
        if (!in_array($responseType, ['auto', 'json', 'text'], true)) {
            throw new ClientException('responseType must be auto, json, or text', null, $operation['id']);
        }
        $format = null;
        foreach ($operation['params'] as $param) {
            if ($param['name'] === 'format') {
                $format = $param;
                break;
            }
        }
        $textFormats = array_values(array_filter($format['enum'] ?? [], static fn($value): bool => !in_array(strtolower((string) $value), ['json', 'application/json'], true)));
        $rawFormat = isset($params['format']) && in_array((string) $params['format'], array_map('strval', $textFormats), true);
        $jsonFormat = isset($params['format']) && in_array(strtolower((string) $params['format']), ['json', 'application/json'], true);
        $isJson = $jsonFormat || stripos($contentType, 'json') !== false || $operation['produces'] === ['application/json'];
        if ($responseType === 'text' || $rawFormat || ($responseType === 'auto' && !$isJson)) {
            return $body;
        }
        try {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new CrawloraException('Invalid JSON response from Crawlora: ' . $exception->getMessage(), null, $operation['id'], $body, $exception);
        }
    }
}
