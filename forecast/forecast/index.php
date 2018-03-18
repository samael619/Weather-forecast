<?php
  const APPID = '1bc9a24fd3dbe20b6e649c15f2f0043e';


  function request ($city) {
    if (!is_string($city)) {
      throw new Exception("Bad request");
    }

    $args = [
      'q' => $city,
      'APPID' => APPID,
      'units' => 'metric',
      'lang' => 'ru'
    ];

    $result = @file_get_contents('http://api.openweathermap.org/data/2.5/forecast?'.http_build_query($args));

    if ($result === FALSE) {
      throw new Exception("Not found");
    }

    return json_decode($result, true);
  }


  function transform ($data) {
    $city = [
      id => $data[city][id],
      name => $data[city][name],
      lat => $data[city][coord][lat],
      lon => $data[city][coord][lon]
    ];

    $weather = [];
    foreach ($data['list'] as $forecast) {
      $weather[] = [
        dt => $forecast[dt_txt],
        temp => $forecast[main][temp],
        pressure => $forecast[main][pressure],
        humidity => $forecast[main][humidity],
        description => $forecast[weather][0][description],
        icon => $forecast[weather][0][icon],
        clouds => $forecast[clouds][all],
        wind_speed => $forecast[wind][speed]
      ];
    }

    return [$city, $weather];
  }


  function findCity($db, $name) {
  	$cityStmt = $db->prepare("
      SELECT *
      FROM city
      WHERE LOWER(name) LIKE LOWER(?)
      LIMIT 1");

    $query = '%'.$name.'%';
    $cityStmt->bind_param('s', $query);
    $cityStmt->execute();

    $res = $cityStmt->get_result();
    $fromDB = $res->num_rows !== 0;

    $cityData = [];
    if ($fromDB) {
      $res->data_seek(0);
      $cityData = $res->fetch_assoc();
  	}

  	return [$fromDB, $cityData];
  }


  function findWeather($db, $cityID) {
  	$weatherStmt = $db->prepare("
      SELECT dt, temp, pressure, humidity, description, icon, clouds, wind_speed
      FROM weather
      WHERE city_id = ?");

    $weatherStmt->bind_param('i', $cityID);
    $weatherStmt->execute();
    $res = $weatherStmt->get_result();

    $weather = [];
    while ($row = $res->fetch_assoc()) {
      $weather[] = $row;
    }

    $weatherStmt->close();

    return $weather;
  }


  function updateCity($db, $cityData) {
  	$updateStmt = $db->prepare("
  	  INSERT INTO city (id, name, lat, lon)
      VALUES (?, ?, ?, ?)");

  	$updateStmt->bind_param('isdd', $cityData[id], $cityData[name], $cityData[lat], $cityData[lon]);
    $updateStmt->execute();
    $updateStmt->close();
  }


  function insertWeather($db, $cityID, $weather) {
  	$query = "INSERT INTO weather (city_id, dt, temp, pressure, humidity, description, icon, clouds, wind_speed) VALUES";
    foreach ($weather as $w) {
      $query .= " ('".$cityID."', '".implode("', '", $w)."'),";
    }
    $query = trim($query, ",");
    $db->query($query);
  }


  function getForecast ($city) {
    $db = new mysqli('localhost:3306', 'root', '', 'forecast');

    if (mysqli_connect_errno()) {
      printf("Подключение невозможно: %s\n", mysqli_connect_error());
      exit();
    }

    list($fromDB, $cityData) = findCity($db, $city);

    if ($fromDB) {
      $weather = findWeather($db, $cityData[id]);
    } else {
      $weather = request($city);
      list($cityData, $weather) = transform($weather);
      updateCity($db, $cityData);
      insertWeather($db, $cityData[id], $weather);
    }

    $db->close();

    return [$cityData, $weather, $fromDB];
  }


  $hasForecast = false;
  if (isset($_GET['city']) && is_string($_GET['city'])) {
    $hasForecast = true;
    try {
      list($cityInfo, $weather, $fromDB) = getForecast($_GET['city']);
    } catch (Exception $e) {
      $weather = "Не найдено";
    }
  }
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Прогноз погоды</title>
</head>
<body>

  <form action="." method="GET">
    <input type="search" name="city" value="<?php echo $_GET[city]; ?>">
  </form>

  <?php
    if ($hasForecast) {
      print_r($cityInfo);
      print_r($weather);
      echo $fromDB ? "Из БД" : "Из API";
    } else {
      echo "Введите город";
    }
  ?>

</body>
</html>
