<?php
  const KEY = '23ab52e97ffed34b5f13dcf466d168cd';
  const URL = 'http://api.openweathermap.org/data/2.5/forecast';


  function request ($city) {
    if (!is_string($city)) {
      throw new Exception("Bad request");
    }

    $args = [
      'q' => $city,
      'APPID' => KEY,
      'units' => 'metric',
      'lang' => 'ru'
    ];

    $result = @file_get_contents(URL.'?'.http_build_query($args));

    if ($result === FALSE) {
      throw new Exception("Not found");
    }

    $data = json_decode($result, true);

    $city = [
      id => $data[city][id],
      name => $data[city][name]
    ];

    $weather = [];
    foreach ($data['list'] as $forecast) {
      $weather[] = [
        dt => $forecast[dt_txt],
        temp => $forecast[main][temp],
        pressure => $forecast[main][pressure],
        humidity => $forecast[main][humidity],
        description => $forecast[weather][0][description],
        clouds => $forecast[clouds][all],
        wind_speed => $forecast[wind][speed],
        wind_deg => $forecast[wind][deg]
      ];
    }

    return [$city, $weather];
  }


  function getForecast ($city) {
    $db = new mysqli('localhost:3306', 'root', '', 'weatherDB');

    if (mysqli_connect_errno()) {
      printf("Ошибка подключения: %s\n", mysqli_connect_error());
      exit();
    }

    $cityTable = $db->prepare("
      SELECT *
      FROM city
      WHERE LOWER(name) LIKE LOWER(?)
      LIMIT 1");

    $query = '%'.$city.'%';
    $cityTable->bind_param('s', $query);
    $cityTable->execute();

    $res = $cityTable->get_result();
    $fromDB = $res->num_rows !== 0;
    
    if ($fromDB) {
      $res->data_seek(0);
      $cityData = $res->fetch_assoc();

      $weatherTable = $db->prepare("
        SELECT dt, temp, pressure, humidity, description, clouds, wind_speed, wind_deg
        FROM weather
        WHERE city_id = ?");

      $weatherTable->bind_param('i', $cityData[id]);
      $weatherTable->execute();
      $res = $weatherTable->get_result();

      $weather = [];
      while ($row = $res->fetch_assoc()) {
        $weather[] = $row;
      }

      $weatherTable->close();
    } else {
      list($cityData, $weather) = request($city);

      $updateCity = $db->prepare("
        INSERT INTO city (id, name)
        VALUES (?, ?)");

      $updateCity->bind_param('is', $cityData[id], $cityData[name]);
      $updateCity->execute();
      $updateCity->close();

      $query = "INSERT INTO weather (city_id, dt, temp, pressure, humidity, description, clouds, wind_speed, wind_deg) VALUES";
      foreach ($weather as $forecast) {
        $query .= " ('".$cityData[id]."', '".implode("', '", $forecast)."'),";
      }
      $query = trim($query, ",");
      $insertWeather = $db->query($query);
    }

    $cityTable->close();

    $db->close();

    return [$cityData, $weather, $fromDB];
  }


  $hasForecast = false;
  if (isset($_GET['city']) && is_string($_GET['city'])) {
    $hasForecast = true;
    try {
      list($city, $weather, $fromDB) = getForecast($_GET['city']);
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
      print_r($city);
      print_r($weather);
      echo $fromDB ? "Погода из БД" : "Погода из API";
    } else {
      echo "Введите запрос (транслитом)";
    }
  ?>

</body>
</html>
