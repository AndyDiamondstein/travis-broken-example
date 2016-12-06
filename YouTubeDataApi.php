<?php

// Sample PHP code for user authorization

// Call set_include_path() as needed to point to your client library.
require_once 'vendor/autoload.php';
require_once 'Google/Client.php';
//require_once 'Google/Service/YouTube.php';
session_start();

/*
 * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
 * Google Developers Console: https://console.developers.google.com/
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE_ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE_ME';
define('CREDENTIALS_PATH', '~/php-yt-oauth2.json');

function getClient() {
  $client = new Google_Client();
  $client->setAuthConfigFile('client_secrets.json');
  $client->addScope(GOOGLE_SERVICE_YOUTUBE::YOUTUBE_FORCE_SSL);
  $client->setRedirectUri('http://localhost');
  $client->setAccessType('offline');

// Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Define an object that will be used to make all API requests.
$client = getClient();
$service = new Google_Service_YouTube($client);

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if (!$client->getAccessToken()) {
  print("no access token, whaawhaaa");
  exit;
}
  

// Sample PHP code for printing API response data

/**
 * Prints results summary
 *
 * @param $response An API response.
 */
function printResults($items) {
    foreach ($items as $item) {
        $itemIdOrType = "";
        if (is_object($item['id'])) {
            $itemIdOrType = "search result: ";
        } elseif (property_exists($item, "rating")) {
            $itemIdOrType = "Rating: ";
        } else {
            $itemIdOrType = $item['id'];
        }

        $title = "";
        $snippetProperties = array("type", "title", "textDisplay", "channelId", "videoId", "hl", "gl", "label");

        if (is_object($item['snippet'])) {
            foreach ($snippetProperties as &$value) {
                if (property_exists($item['snippet'], $value)) {
                    $title = $item["snippet"][$value];
                    break;
                }
            }
        } else {
            $title = $item["rating"];
        }

        print($itemIdOrType . ": " . $title . "\n");
    }
}

/*
 * This example retrieves the 25 most recent activities for the Google Developers 
 * channel. It retrieves the snippet and contentDetails parts for each activity 
 * resource. 
*/
function activitiesList($youtube, $part, $channelId, $maxResults) {
    $response = $youtube->activities->listActivities(
        $part,
        array(
            'channelId' => $channelId,
            'maxResults' => $maxResults
        )
    );

    printResults($response);
}

activitiesList($service, 'snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw', 25);

/*
 * This example retrieves the 25 most recent activities performed by the user 
 * authorizing the API request. 
*/
function activitiesListMine($youtube, $part, $maxResults, $mine) {
    $response = $youtube->activities->listActivities(
        $part,
        array(
            'maxResults' => $maxResults,
            'mine' => $mine
        )
    );

    printResults($response);
}

activitiesListMine($service, 'snippet,contentDetails', 25, true);

/*
 * This example lists caption tracks available for the Volvo Trucks "Epic Split" 
 * commercial, featuring Jean-Claude Van Damme. (This video was selected because 
 * it has many available caption tracks and also because it is awesome.) 
*/
function captionsList($youtube, $part, $videoId) {
    $response = $youtube->captions->listCaptions(
        $part,
        $videoId, 
        array(
            
        )
    );

    printResults($response);
}

captionsList($service, 'snippet', 'M7FIvfx5J10');

/*
 * This example retrieves channel data for the GoogleDevelopers YouTube channel. 
 * It uses the id request parameter to identify the channel by its YouTube channel 
 * ID. 
*/
function channelsListById($youtube, $part, $id) {
    $response = $youtube->channels->listChannels(
        $part,
        array(
            'id' => $id
        )
    );

    printResults($response);
}

channelsListById($service, 'snippet,contentDetails,statistics', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');

/*
 * This example retrieves channel data for the GoogleDevelopers YouTube channel. 
 * It uses the forUsername request parameter to identify the channel by its 
 * YouTube username. 
*/
function channelsListByUsername($youtube, $part, $forUsername) {
    $response = $youtube->channels->listChannels(
        $part,
        array(
            'forUsername' => $forUsername
        )
    );

    printResults($response);
}

channelsListByUsername($service, 'snippet,contentDetails,statistics', 'GoogleDevelopers');

/*
 * This example retrieves the channel data for the authorized user's YouTube 
 * channel. It uses the mine request parameter to indicate that the API should 
 * only return channels owned by the user authorizing the request. 
*/
function channelsListMine($youtube, $part, $mine) {
    $response = $youtube->channels->listChannels(
        $part,
        array(
            'mine' => $mine
        )
    );

    printResults($response);
}

channelsListMine($service, 'snippet,contentDetails,statistics', true);

/*
 * This example retrieves the channel sections shown on the Google Developers 
 * channel, using the channelId request parameter to identify the channel. 
*/
function channelSectionsListById($youtube, $part, $channelId) {
    $response = $youtube->channelSections->listChannelSections(
        $part,
        array(
            'channelId' => $channelId
        )
    );

    printResults($response);
}

channelSectionsListById($service, 'snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');

/*
 * This example retrieves the channel sections shown on the authorized user's 
 * channel. It uses the mine request parameter to indicate that the API should 
 * return channel sections on that channel. 
*/
function channelSectionsListMine($youtube, $part, $mine) {
    $response = $youtube->channelSections->listChannelSections(
        $part,
        array(
            'mine' => $mine
        )
    );

    printResults($response);
}

channelSectionsListMine($service, 'snippet,contentDetails', true);

/*
 * This example retrieves comment replies for a specified comment, which is 
 * identified by the parentId request parameter. In this example, the parent 
 * comment is the first comment on a video about Apps Script. The video was chosen 
 * because this particular comment had multiple replies (in multiple languages) 
 * and also because Apps Script is really useful. 
*/
function commentsList($youtube, $part, $parentId) {
    $response = $youtube->comments->listComments(
        $part,
        array(
            'parentId' => $parentId
        )
    );

    printResults($response);
}

commentsList($service, 'snippet', 'z13icrq45mzjfvkpv04ce54gbnjgvroojf0');

/*
 * This example retrieves all comment threads associated with a particular 
 * channel. The response could include comments about the channel or about the 
 * channel's videos. The request's allThreadsRelatedToChannelId parameter 
 * identifies the channel. 
*/
function commentThreadsListAllThreadsByChannelId($youtube, $part, $allThreadsRelatedToChannelId) {
    $response = $youtube->commentThreads->listCommentThreads(
        $part,
        array(
            'allThreadsRelatedToChannelId' => $allThreadsRelatedToChannelId
        )
    );

    printResults($response);
}

commentThreadsListAllThreadsByChannelId($service, 'snippet,replies', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');

/*
 * This example retrieves all comment threads about the specified channel. The 
 * request's channelId parameter identifies the channel. The response does not 
 * include comments left on videos that the channel uploaded. 
*/
function commentThreadsListByChannelId($youtube, $part, $channelId) {
    $response = $youtube->commentThreads->listCommentThreads(
        $part,
        array(
            'channelId' => $channelId
        )
    );

    printResults($response);
}

commentThreadsListByChannelId($service, 'snippet,replies', 'UCAuUUnT6oDeKwE6v1NGQxug');

/*
 * This example retrieves all comment threads associated with a particular video. 
 * The request's videoId parameter identifies the video. 
*/
function commentThreadsListByVideoId($youtube, $part, $videoId) {
    $response = $youtube->commentThreads->listCommentThreads(
        $part,
        array(
            'videoId' => $videoId
        )
    );

    printResults($response);
}

commentThreadsListByVideoId($service, 'snippet,replies', 'm4Jtj2lCMAA');

/*
 * This example retrieves a list of application languages that the YouTube website 
 * supports. The example sets the hlparameter value to es_MX, indicating that text 
 * values in the API response should be provided in that language. That 
 * parameter's default value is en_US. 
*/
function i18nLanguagesList($youtube, $part, $hl) {
    $response = $youtube->i18nLanguages->listI18nLanguages(
        $part,
        array(
            'hl' => $hl
        )
    );

    printResults($response);
}

i18nLanguagesList($service, 'snippet', 'es_MX');

/*
 * This example retrieves a list of content regions that the YouTube website 
 * supports. The example sets the hlparameter value to es_MX, indicating that text 
 * values in the API response should be provided in that language. That 
 * parameter's default value is en_US. 
*/
function i18nRegionsList($youtube, $part, $hl) {
    $response = $youtube->i18nRegions->listI18nRegions(
        $part,
        array(
            'hl' => $hl
        )
    );

    printResults($response);
}

i18nRegionsList($service, 'snippet', 'es_MX');

/*
 * This example retrieves the list of videos in a specified playlist. The 
 * request's playlistId parameter identifies the playlist.

Note that the API 
 * response does not include metadata about the playlist itself, such as the 
 * playlist's title and description. Additional metadata about the videos in the 
 * playlist can also be retrieved using the videos.listmethod. 
*/
function playlistItemsListByPlaylistId($youtube, $part, $maxResults, $playlistId) {
    $response = $youtube->playlistItems->listPlaylistItems(
        $part,
        array(
            'maxResults' => $maxResults,
            'playlistId' => $playlistId
        )
    );

    printResults($response);
}

playlistItemsListByPlaylistId($service, 'snippet,contentDetails', 25, 'PLBCF2DAC6FFB574DE');

/*
 * This example retrieves playlists owned by the YouTube channel that the 
 * request's channelId parameter identifies. 
*/
function playlistsListByChannelId($youtube, $part, $channelId, $maxResults) {
    $response = $youtube->playlists->listPlaylists(
        $part,
        array(
            'channelId' => $channelId,
            'maxResults' => $maxResults
        )
    );

    printResults($response);
}

playlistsListByChannelId($service, 'snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw', 25);

/*
 * This example retrieves playlists created in the authorized user's YouTube 
 * channel. It uses the mine request parameter to indicate that the API should 
 * only return playlists owned by the user authorizing the request. 
*/
function playlistsListMine($youtube, $part, $mine) {
    $response = $youtube->playlists->listPlaylists(
        $part,
        array(
            'mine' => $mine
        )
    );

    printResults($response);
}

playlistsListMine($service, 'snippet,contentDetails', true);

/*
 * This example retrieves the first 25 search results associated with the keyword 
 * surfing. Since the request doesn't specify a value for the type request 
 * parameter, the response can include videos, playlists, and channels. 
*/
function searchListByKeyword($youtube, $part, $maxResults, $q, $type) {
    $response = $youtube->search->listSearch(
        $part,
        array(
            'maxResults' => $maxResults,
            'q' => $q,
            'type' => $type
        )
    );

    printResults($response);
}

searchListByKeyword($service, 'snippet', 25, 'surfing', 'video');

/*
 * This example retrieves search results associated with the keyword surfing that 
 * also specify in their metadata a geographic location within 10 miles of the 
 * point identified by the location parameter value. (The sample request specifies 
 * a point on the North Shore of Oahu, Hawaii . The request retrieves the top five 
 * results, which is the default number returned when the maxResults parameter is 
 * not specified. 
*/
function searchListByLocation($youtube, $part, $location, $locationRadius, $q, $type) {
    $response = $youtube->search->listSearch(
        $part,
        array(
            'location' => $location,
            'locationRadius' => $locationRadius,
            'q' => $q,
            'type' => $type
        )
    );

    printResults($response);
}

searchListByLocation($service, 'snippet', '21.5922529,-158.1147114', '10mi', 'surfing', 'video');

/*
 * This example retrieves a list of acdtive live broadcasts (see the eventType 
 * parameter value) that are associated with the keyword news. Since the eventType 
 * parameter is set, the request must also set the type parameter value to video. 
*/
function searchListLiveEvents($youtube, $part, $eventType, $maxResults, $q, $type) {
    $response = $youtube->search->listSearch(
        $part,
        array(
            'eventType' => $eventType,
            'maxResults' => $maxResults,
            'q' => $q,
            'type' => $type
        )
    );

    printResults($response);
}

searchListLiveEvents($service, 'snippet', 'live', 25, 'news', 'video');

/*
 * This example searches within the authorized user's videos for videos that match 
 * the keyword fun. The forMine parameter indicates that the response should only 
 * search within the authorized user's videos. Also, since this request uses the 
 * forMine parameter, it must also set the type parameter value to video.

If you 
 * have not uploaded any videos associated with that term, you will not see any 
 * items in the API response list. 
*/
function searchListMine($youtube, $part, $maxResults, $forMine, $q, $type) {
    $response = $youtube->search->listSearch(
        $part,
        array(
            'maxResults' => $maxResults,
            'forMine' => $forMine,
            'q' => $q,
            'type' => $type
        )
    );

    printResults($response);
}

searchListMine($service, 'snippet', 25, true, 'fun', 'video');

/*
 * This example sets the relatedToVideoId parameter to retrieve a list of videos 
 * related to that video. Since the relatedToVideoId parameter is set, the request 
 * must also set the type parameter value to video. 
*/
function searchListRelatedVideos($youtube, $part, $relatedToVideoId, $type) {
    $response = $youtube->search->listSearch(
        $part,
        array(
            'relatedToVideoId' => $relatedToVideoId,
            'type' => $type
        )
    );

    printResults($response);
}

searchListRelatedVideos($service, 'snippet', 'Ks-_Mh1QhMc', 'video');

/*
 * This example retrieves a list of channels that the specified channel subscribes 
 * to. In this example, the API response lists channels to which the GoogleDevelopers channel 
 * subscribes. 
*/
function subscriptionsListByChannelId($youtube, $part, $channelId) {
    $response = $youtube->subscriptions->listSubscriptions(
        $part,
        array(
            'channelId' => $channelId
        )
    );

    printResults($response);
}

subscriptionsListByChannelId($service, 'snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw');

/*
 * This example determines whether the user authorizing the API request subscribes 
 * to the channel that the forChannelId parameter identifies. To check whether 
 * another channel (instead of the authorizing user's channel) subscribes to the 
 * specified channel, remove the mine parameter from this request and add the channelId parameter 
 * instead.

In this example, the API response contains one item if you subscribe to 
 * the GoogleDevelopers channel. Otherwise, the request does not return any items. 
*/
function subscriptionsListForChannelId($youtube, $part, $forChannelId, $mine) {
    $response = $youtube->subscriptions->listSubscriptions(
        $part,
        array(
            'forChannelId' => $forChannelId,
            'mine' => $mine
        )
    );

    printResults($response);
}

subscriptionsListForChannelId($service, 'snippet,contentDetails', 'UC_x5XG1OV2P6uZZ5FSM9Ttw', true);

/*
 * This example uses the mySubscribers parameter to retrieve the list of channels 
 * to which the authorized user subscribes. 
*/
function subscriptionsListMySubscribers($youtube, $part, $mySubscribers) {
    $response = $youtube->subscriptions->listSubscriptions(
        $part,
        array(
            'mySubscribers' => $mySubscribers
        )
    );

    printResults($response);
}

subscriptionsListMySubscribers($service, 'snippet,contentDetails,subscriberSnippet', true);

/*
 * This example uses the mine parameter to retrieve a list of channels that 
 * subscribe to the authenticated user's channel. 
*/
function subscriptionsListMySubscriptions($youtube, $part, $mine) {
    $response = $youtube->subscriptions->listSubscriptions(
        $part,
        array(
            'mine' => $mine
        )
    );

    printResults($response);
}

subscriptionsListMySubscriptions($service, 'snippet,contentDetails', true);

/*
 * This example shows how to retrieve a list of reasons that can be used to report 
 * abusive videos. You can retrieve the text labels in other languages by 
 * specifying a value for the hl request parameter. 
*/
function videoAbuseReportReasonsList($youtube, $part) {
    $response = $youtube->videoAbuseReportReasons->listVideoAbuseReportReasons(
        $part,
        array(
            
        )
    );

    printResults($response);
}

videoAbuseReportReasonsList($service, 'snippet');

/*
 * This example retrieves a list of categories that can be associated with YouTube 
 * videos in the United States. The regionCode parameter specifies the country for 
 * which categories are being retrieved. 
*/
function videoCategoriesList($youtube, $part, $regionCode) {
    $response = $youtube->videoCategories->listVideoCategories(
        $part,
        array(
            'regionCode' => $regionCode
        )
    );

    printResults($response);
}

videoCategoriesList($service, 'snippet', 'US');

/*
 * This example uses the regionCode to retrieve a list of categories that can be 
 * associated with YouTube videos in Spain. It also uses the hl parameter to 
 * indicate that text labels in the response should be specified in Spanish. 
*/
function videoCategoriesListForRegion($youtube, $part, $hl, $regionCode) {
    $response = $youtube->videoCategories->listVideoCategories(
        $part,
        array(
            'hl' => $hl,
            'regionCode' => $regionCode
        )
    );

    printResults($response);
}

videoCategoriesListForRegion($service, 'snippet', 'es', 'ES');

/*
 * This example retrieves information about a specific video. It uses the id 
 * parameter to identify the video. 
*/
function videosListById($youtube, $part, $id) {
    $response = $youtube->videos->listVideos(
        $part,
        array(
            'id' => $id
        )
    );

    printResults($response);
}

videosListById($service, 'snippet,contentDetails,statistics', 'Ks-_Mh1QhMc');

/*
 * This example retrieves a list of YouTube's most popular videos. The regionCode 
 * parameter identifies the country for which you are retrieving videos. The 
 * sample code is set to default to return the most popular videos in the United 
 * States. You could also use the videoCategoryId parameter to retrieve the most 
 * popular videos in a particular category. 
*/
function videosListMostPopular($youtube, $part, $chart, $regionCode, $videoCategoryId) {
    $response = $youtube->videos->listVideos(
        $part,
        array(
            'chart' => $chart,
            'regionCode' => $regionCode,
            'videoCategoryId' => $videoCategoryId
        )
    );

    printResults($response);
}

videosListMostPopular($service, 'snippet,contentDetails,statistics', 'mostPopular', 'US', '');

/*
 * This example retrieves information about a group of videos. The id parameter 
 * value is a comma-separated list of YouTube video IDs. You might issue a request 
 * like this to retrieve additional information about the items in a playlist or 
 * the results of a search query. 
*/
function videosListMultipleIds($youtube, $part, $id) {
    $response = $youtube->videos->listVideos(
        $part,
        array(
            'id' => $id
        )
    );

    printResults($response);
}

videosListMultipleIds($service, 'snippet,contentDetails,statistics', 'Ks-_Mh1QhMc,c0KYU2j0TM4,eIho2S0ZahI');

/*
 * This example retrieves a list of videos liked by the user authorizing the API 
 * request. By setting the rating parameter value to dislike, you could also use 
 * this code to retrieve disliked videos. 
*/
function videosListMyRatedVideos($youtube, $part, $myRating) {
    $response = $youtube->videos->listVideos(
        $part,
        array(
            'myRating' => $myRating
        )
    );

    printResults($response);
}

videosListMyRatedVideos($service, 'snippet,contentDetails,statistics', 'like');

/*
 * This example retrieves the rating that the user authorizing the request gave to 
 * a particular video. In this example, the video is of Amy Cuddy's TED talk about 
 * body language. 
*/
function videosGetRating($service, $id) {
    $response = $service->videos->getRating($id);
    printResults($response);
}

videosGetRating($youtube, 'Ks-_Mh1QhMc,c0KYU2j0TM4,eIho2S0ZahI');

?>
