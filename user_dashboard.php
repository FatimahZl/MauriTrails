<?php
session_start();
require_once "config.php";


if (!isset($_SESSION["user_id"])) {
  header("location: index.php");
  exit;
}

$user_id = $_SESSION["user_id"];
$display_name = "User";

$search_term = "";
if (isset($_GET['search'])) {
  $search_term = mysqli_real_escape_string($conn, $_GET['search']);
}

$query = "SELECT * FROM places";
if (!empty($search_term)) {
  $query .= " WHERE name LIKE '%$search_term%' OR description LIKE '%$search_term%' OR category LIKE '%$search_term%'";
}

$places_result = mysqli_query($conn, $query);

/*function awardPoints($user_id, $activity_type)
{
  global $conn;
  $points = 0;


  switch ($activity_type) {
    case 'visit':
      $points = 10;
      break;
    case 'review':
      $points = 20;
      break;
    case 'photo':
      $points = 30;
      break;
    case 'share':
      $points = 15;
      break;
  }


  $update_query = "UPDATE users SET points = points + " . $points . " WHERE user_id = " . $user_id;
  $conn->query($update_query);


  $log_query = "INSERT INTO activities (user_id, type, points, timestamp) 
                  VALUES (" . $user_id . ", '" . $activity_type . "', " . $points . ", NOW())";
  $conn->query($log_query);
}*/
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title></title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap"
    rel="stylesheet" />
  <link
    rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,700,1,0" />
  <link
    href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css"
    rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin="" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
  <link rel="stylesheet" href="https://unpkg.com/akar-icons-fonts@1.1.22/src/css/akar-icons.css">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<style>
  * {
    box-sizing: border-box;
    padding: 0;
    margin: 0;
  }

  body {
    margin: 0;
    background: linear-gradient(45deg, #0b3842, #9f7743);
    color: #fdfdfd;
    min-height: 100vh;
    overflow-x: hidden;
    font-family:
      "Poppins",
      -apple-system,
      BlinkMacSystemFont,
      "Segoe UI",
      Roboto,
      sans-serif;

  }

  .nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(45, 87, 96, 0.95);
    display: flex;
    justify-content: space-around;
    padding: 12px 0;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    z-index: 100;
  }

  .nav button {
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    font-size: 13px;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin: 0 1px;
  }

  .nav button:hover {
    background-color: rgba(145, 168, 203, 0.3);
    color: #ffffff;
  }

  .nav button.active {
    background-color: rgba(168, 159, 253, 0.3);
    color: #ffffff;
  }

  /* Tabs */
  .tab {
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    animation: fadeIn 0.3s ease;
    margin-top: 100px;
    margin-bottom: 100px;
  }

  .tab.hidden {
    display: none;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .top-nav {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: rgba(45, 87, 96, 0.95);
    padding: 1rem 5%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fdfdfd;
    text-decoration: none;
  }

  .logo i {
    font-size: 2rem;
    color: #cc9142;
  }

  .logout {
    display: flex;
    list-style: none;
  }

  .logout a {
    color: #fdfdfd;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
    display: flex;
  }

  .logout a:hover {
    color: #cc9142;
  }

  .hero {
    min-height: 100vh;
    display: flex;
    width: 100%;
    align-items: center;
    justify-content: center;
    padding: 80px 5% 40px;
    position: relative;
    overflow: hidden;
  }

  .hero::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url("uploads/images/hero-bg.jpg") repeat-y center center/cover;
    filter: brightness(0.5);
    z-index: 0;
  }

  .hero-content {
    max-width: 1200px;
    text-align: center;
    z-index: 1;
    animation: fadeInUp 1s ease;
  }

  .hero h1 {
    font-size: 50px;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #fdfdfd;
  }

  .hero p {
    font-size: 20px;
    color: #d9dbe0;
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
  }

  .hero-button {
    display: flex;
    gap: 20px;
    justify-content: center;
    color: #fdfdfd;
  }

  .hero-button button.btn {
    color: inherit;
    border-color: inherit;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .hero-button button.btn:hover {
    background: #e8ecf4;
    color: #384251;
    border-color: #e8ecf4;
  }

  .card {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    background: #2d5760;
    border: none;
    margin-bottom: 20px;
  }

  .card-bg {
    position: absolute;
    z-index: 2;
    top: 0;
    left: 0;
    bottom: 0;
    width: 50%;
    background: #0b3842;
    border-radius: 12px;
    translate: 0 0;
    transition: 0.65s ease-in-out;
  }

  .card-img-top {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-top-left-radius: 16px;
    border-top-right-radius: 16px;
  }

  #map {
    width: 100%;
    height: 70vh;
    border-radius: 12px;
    margin-bottom: 80px;
    z-index: 1;
  }

  /* Leaflet Routing Machine Styling */
  .leaflet-routing-container {
    background-color: white;
    color: #333;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  }

  .leaflet-routing-container h2,
  .leaflet-routing-container h3 {
    color: #333;
    font-size: 14px;
    margin: 5px 0;
  }

  .leaflet-routing-alt {
    background-color: #f8f9fa;
    padding: 8px;
    margin: 5px 0;
    border-radius: 4px;
  }

  .leaflet-routing-alt table {
    color: #333;
  }

  .leaflet-routing-alt-minimized {
    background-color: white;
    color: #333;
  }

  .leaflet-routing-icon {
    background-color: white;
  }

  .leaflet-routing-collapse-btn {
    color: #333;
    background-color: white;
  }


  .leaflet-routing-alt tr td {
    color: #333;
    padding: 4px;
  }


  .leaflet-routing-alt h3 {
    font-weight: 600;
    color: #0066cc;
  }


  .leaflet-routing-alt tbody tr {
    border-bottom: 1px solid #e0e0e0;
  }


  .leaflet-routing-collapse-btn:after {
    color: #333;
  }


  .custom-popup .leaflet-popup-content-wrapper {
    border-radius: 12px;
    padding: 8px;
  }

  .custom-popup .leaflet-popup-content {
    margin: 8px;
  }

  .custom-popup button:hover {
    opacity: 0.9;
    transform: translateY(-1px);
  }


#tab-game:not(.hidden) {
  min-height: 100vh;
  display: flex; 
  align-items: center;
  padding: 40px 0;
}


.wheel-box {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}


.wheel-wrapper {
    position: relative;
    width: 500px;
    height: 500px;
    margin: 20px auto;
}


.game-container {
    width: 100%;
    height: 100%;
    background-color: #ccc;
    border: 10px solid #fff;
    position: relative;
    overflow: hidden;
    transition: transform 4s cubic-bezier(0.15, 0, 0.15, 1);
    border-radius: 50%;
    box-shadow: 0 0 20px rgba(0,0,0,0.2);
}


.game-container div {
    height: 50%;
    width: 38.3%; 
    position: absolute;
    top: 0;
    left: 50%;
    transform-origin: bottom center; 
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding-top: 40px;
    font-size: 16px;
    font-weight: bold;
    color: #fff;
    clip-path: polygon(100% 0, 50% 100%, 0 0);
    text-align: center;
}

.game-container div i {
    font-size: 32px;
    margin-bottom: 8px;
}

.game-container div span {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}


.one   { 
    background: linear-gradient(135deg, #0066cc, #0052a3);
    transform: translateX(-50%) rotate(0deg); 
}
.two   { 
    background: linear-gradient(135deg, #0b3842, #082a31);
    transform: translateX(-50%) rotate(45deg); 
}
.three { 
    background: linear-gradient(135deg, #2d5760, #1f3d45);
    transform: translateX(-50%) rotate(90deg); 
}
.four  { 
    background: linear-gradient(135deg, #7a2c2c, #5c2121);
    transform: translateX(-50%) rotate(135deg); 
}
.five  { 
    background: linear-gradient(135deg, #cc9142, #a87435);
    transform: translateX(-50%) rotate(180deg); 
}
.six   { 
    background: linear-gradient(135deg, #89837a, #6a665f);
    transform: translateX(-50%) rotate(225deg); 
}
.seven { 
    background: linear-gradient(135deg, #e2868b, #d66a70);
    transform: translateX(-50%) rotate(270deg); 
}
.eight { 
    background: linear-gradient(135deg, #2a2113, #1a150c);
    transform: translateX(-50%) rotate(315deg); 
}

.one h5{
  font-size: 12px;
  transform: rotate(-90deg);
}
.two h5{
  font-size: 12px;
  transform: rotate(-90deg);
}
.three h5{
  font-size: 12px;
  transform: rotate(-90deg);
  top: 20%;
}
.four h5{
  font-size: 12px;
  transform: rotate(-90deg);
  top: 20%;
}
.five h5{
  font-size: 12px;
  transform: rotate(-90deg);
  top: 20%;
}
.six h5{
  font-size: 12px;
  transform: rotate(-90deg);
  top: 20%;
}
.seven h5{
  font-size: 12px;
  transform: rotate(-90deg);
  top: 20%;
}
.eight h5{
  font-size: 12px;
  transform: rotate(-90deg);
  top: 20%;
}


#spin {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
    background: linear-gradient(145deg, #ffffff, #e0e0e0);
    border: 5px solid #333;
    font-weight: bold;
    font-size: 18px;
    color: #333;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 6px 15px rgba(0,0,0,0.4);
    transition: all 0.3s ease;
}

#spin:hover {
    transform: translate(-50%, -50%) scale(1.1);
    box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    background: linear-gradient(145deg, #f0f0f0, #ffffff);
}

#spin:active {
    transform: translate(-50%, -50%) scale(0.95);
}


.arrow {
    position: absolute;
    top: -35px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
    width: 0; 
    height: 0; 
    border-left: 20px solid transparent;
    border-right: 20px solid transparent;
    border-top: 40px solid #ff4444;
    filter: drop-shadow(0 3px 5px rgba(0,0,0,0.3));
}

/* Activity info cards styling */
.card h6 {
    margin: 8px 0 4px 0;
    font-weight: 600;
}

.card small {
    color: rgba(255, 255, 255, 0.7);
    font-size: 12px;
}


</style>

<body>
  <nav class="top-nav">
    <a href="user_dashboard.php" class="logo">
      <i class="ai-location"></i>
      MauriTrails
    </a>
    <ul class="logout">
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>


  <div class="tab hidden" id="tab-home">
    <section class="hero">
      <div class="hero-content">
        <h1>Discover the Beauty of Mauritius</h1>
        <p>Explore cultural heritage sites, stunning landscapes and hidden treasures through interactive trails.</p>
        <div class="hero-button">
          <button onclick="switchTab('place')" class="btn">Explore Places</button>
          <button onclick="switchTab('map')" class="btn">View Map</button>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid mt-4">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h2>Heritage Sites</h2>
                <p class="text-muted"></p>
              </div>
              <div class="hero-button">
                <button type="button" class="btn" data-bs-toggle="modal" onclick="switchTab('place')">
                  View All
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="row">
                <?php
                $query = "SELECT * FROM places WHERE category ='Heritage Sites' LIMIT 3";
                $home_places_result = mysqli_query($conn, $query);
                ?>
                <?php if (mysqli_num_rows($home_places_result) > 0): ?>
                  <?php while ($place = mysqli_fetch_assoc($home_places_result)): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                      <div class="card h-100">
                        <img src="uploads/images/<?php echo htmlspecialchars($place['main_image']); ?>" class="card-img-top" alt="...">
                        <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?php echo htmlspecialchars($place['name']); ?></h5>
                          <p class="card-text"><?php echo htmlspecialchars(substr($place['description'], 0, 100)) . '...'; ?></p>
                          <div class="d-flex gap-2 mt-auto">
                            <a href="view_place_details.php?place_id=<?php echo $place['place_id']; ?>" class="btn btn-primary mt-auto flex-fill">View Details</a>
                            <button class="btn btn-outline-success mt-2 flex-fill"
                              onclick="switchTab('map');getDirections(<?php echo $place['latitude']; ?>, <?php echo $place['longitude']; ?>)">
                              Get Directions
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid mt-4">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h2>Popular Places</h2>
                <p class="text-muted"></p>
              </div>
              <div class="hero-button">
                <button type="button" class="btn" data-bs-toggle="modal" onclick="switchTab('map')">
                  View All
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="row">
                <?php
                $query = "SELECT * FROM places WHERE category ='Popular Places' LIMIT 3";
                $home_places_result = mysqli_query($conn, $query);
                ?>
                <?php if (mysqli_num_rows($home_places_result) > 0): ?>
                  <?php while ($place = mysqli_fetch_assoc($home_places_result)): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                      <div class="card h-100">
                        <img src="uploads/images/<?php echo htmlspecialchars($place['main_image']); ?>" class="card-img-top" alt="...">
                        <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?php echo htmlspecialchars($place['name']); ?></h5>
                          <p class="card-text"><?php echo htmlspecialchars(substr($place['description'], 0, 100)) . '...'; ?></p>
                          <a href="view_place_details.php?place_id=<?php echo $place['place_id']; ?>" class="btn btn-primary mt-auto">View Details</a>
                          <button class="btn btn-outline-success mt-2"
                            onclick="switchTab('map');getDirections(<?php echo $place['latitude']; ?>, <?php echo $place['longitude']; ?>)">
                            Get Directions
                          </button>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid mt-4">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h2>Activities</h2>
                <p class="text-muted"></p>
              </div>
              <div class="hero-button">
                <button type="button" class="btn" data-bs-toggle="modal" onclick="switchTab('map')">
                  View All
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="row">
                <?php
                $query = "SELECT * FROM places WHERE category ='Activities' LIMIT 3";
                $home_places_result = mysqli_query($conn, $query);
                ?>
                <?php if (mysqli_num_rows($home_places_result) > 0): ?>
                  <?php while ($place = mysqli_fetch_assoc($home_places_result)): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                      <div class="card h-100">
                        <img src="uploads/images/<?php echo htmlspecialchars($place['main_image']); ?>" class="card-img-top" alt="...">
                        <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?php echo htmlspecialchars($place['name']); ?></h5>
                          <p class="card-text"><?php echo htmlspecialchars(substr($place['description'], 0, 100)) . '...'; ?></p>
                          <a href="view_place_details.php?place_id=<?php echo $place['place_id']; ?>" class="btn btn-primary mt-auto">View Details</a>
                          <button class="btn btn-outline-success mt-2"
                            onclick="switchTab('map');getDirections(<?php echo $place['latitude']; ?>, <?php echo $place['longitude']; ?>)">
                            Get Directions
                          </button>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section>
      <div class="container-fluid mt-4">
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div>
                <h2>Nature Spots</h2>
                <p class="text-muted"></p>
              </div>
              <div class="hero-button">
                <button type="button" class="btn" data-bs-toggle="modal" onclick="switchTab('map')">
                  View All
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="row">
                <?php
                $query = "SELECT * FROM places WHERE category ='Nature Spots' LIMIT 3";
                $home_places_result = mysqli_query($conn, $query);
                ?>
                <?php if (mysqli_num_rows($home_places_result) > 0): ?>
                  <?php while ($place = mysqli_fetch_assoc($home_places_result)): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                      <div class="card h-100">
                        <img src="uploads/images/<?php echo htmlspecialchars($place['main_image']); ?>" class="card-img-top" alt="...">
                        <div class="card-body d-flex flex-column">
                          <h5 class="card-title"><?php echo htmlspecialchars($place['name']); ?></h5>
                          <p class="card-text"><?php echo htmlspecialchars(substr($place['description'], 0, 100)) . '...'; ?></p>
                          <a href="view_place_details.php?place_id=<?php echo $place['place_id']; ?>" class="btn btn-primary mt-auto">View Details</a>
                          <button class="btn btn-outline-success mt-2"
                            onclick="switchTab('map');getDirections(<?php echo $place['latitude']; ?>, <?php echo $place['longitude']; ?>)">
                            Get Directions
                          </button>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>


  </div>


  <div class="tab hidden" id="tab-place">
    <div class="container-fluid mt-4">
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h1>Explore the Beauty of Mauritius</h1>
              <p class="text-muted"></p>
            </div>
            <div class="hero-button">
              <button type="button" class="btn" data-bs-toggle="modal" onclick="switchTab('map')">
                View Map
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="card mb-4" style="height: auto; width: 100%; border: none; padding: 20px;">
        <div class="card-header bg-transparent border-0">
          <h5><i class="fas fa-filter"></i> Filter Places</h5>
        </div>
        <div class="card-body">
          <form action="" method="GET" class="row g-3">
            <div class="col-md-9">
              <label class="form-label">Search</label>
              <input type="text" name="search" class="form-control" placeholder="Search places by name or description">
            </div>
            <div class="col-md-2">
              <label class="form-label">&nbsp;</label>
              <button type="submit" class="btn btn-primary d-block w-100">Search</button>
            </div>
          </form>
        </div>
      </div>
      <div class="row">
        <?php if (mysqli_num_rows($places_result) > 0): ?>
          <?php while ($place = mysqli_fetch_assoc($places_result)): ?>
            <div class="col-lg-4 col-md-6 mb-4">
              <div class="card h-100">
                <img src="uploads/images/<?php echo htmlspecialchars($place['main_image']); ?>" class="card-img-top" alt="...">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title"><?php echo htmlspecialchars($place['name']); ?></h5>
                  <p class="card-text"><?php echo htmlspecialchars(substr($place['description'], 0, 100)) . '...'; ?></p>
                  <a href="view_place_details.php?place_id=<?php echo $place['place_id']; ?>" class="btn btn-primary mt-auto">View Details</a>
                  <button class="btn btn-outline-success mt-2"
                    onclick="switchTab('map');getDirections(<?php echo $place['latitude']; ?>, <?php echo $place['longitude']; ?>)">
                    Get Directions
                  </button>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>


  <div class="tab hidden" id="tab-scan">
    <div class="screen">
      <div class="header">
      </div>
      <h2>Scan the QR Code</h2>
      <p>Align the QR code inside the box.</p>
      <div id="reader"></div>
    </div>
  </div>

  <div class="tab hidden" id="tab-map">
    <h1>Map</h1>
    <div id="map"></div>
  </div>


  <div class="tab hidden" id="tab-profile">
  <div class="container-fluid mt-4">
    
    <!-- Profile Header -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body text-center">
            <div class="profile-avatar mb-3">
              <img src="uploads/avatars/default-avatar.png" alt="Profile" class="rounded-circle" 
                   style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #cc9142;">
            </div>
            <h3><?php echo htmlspecialchars($display_name); ?></h3>
            <p class="text-muted">Explorer Level <span id="user-level">1</span></p>
            <button class="btn btn-primary btn-sm" onclick="editProfile()">
              <i class="fas fa-edit"></i> Edit Profile
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-6 col-md-3 mb-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <i class="fas fa-map-marked-alt fa-2x mb-2" style="color: #cc9142;"></i>
            <h4 id="places-visited">0</h4>
            <small>Places Visited</small>
          </div>
        </div>
      </div>
      
      <div class="col-6 col-md-3 mb-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <i class="fas fa-star fa-2x mb-2" style="color: #cc9142;"></i>
            <h4 id="total-points">0</h4>
            <small>Total Points</small>
          </div>
        </div>
      </div>
      
      <div class="col-6 col-md-3 mb-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <i class="fas fa-qrcode fa-2x mb-2" style="color: #cc9142;"></i>
            <h4 id="qr-scanned">0</h4>
            <small>QR Scanned</small>
          </div>
        </div>
      </div>
      
      <div class="col-6 col-md-3 mb-3">
        <div class="card text-center h-100">
          <div class="card-body">
            <i class="fas fa-trophy fa-2x mb-2" style="color: #cc9142;"></i>
            <h4 id="achievements">0</h4>
            <small>Achievements</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5><i class="fas fa-chart-line"></i> Your Progress</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-2">
                <span>Level <span id="current-level">1</span> Progress</span>
                <span><span id="current-xp">0</span>/<span id="needed-xp">100</span> XP</span>
              </div>
              <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: 0%;" 
                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="level-progress-bar">0%</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Daily Challenges Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-bullseye"></i> Daily Challenges</h5>
            <button class="btn btn-sm btn-primary" onclick="switchTab('game')">
              <i class="fas fa-sync-alt"></i> Get New Challenge
            </button>
          </div>
          <div class="card-body">
            <div id="daily-challenges-list">
              <!-- Challenges will be loaded here via JavaScript -->
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5><i class="fas fa-history"></i> Recent Activity</h5>
          </div>
          <div class="card-body">
            <div class="list-group" id="recent-activity">
              <!-- Activity items will be loaded here -->
              <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Achievements Section -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5><i class="fas fa-award"></i> Achievements</h5>
          </div>
          <div class="card-body">
            <div class="row" id="achievements-grid">
              <!-- Achievement badges -->
              <div class="col-4 col-md-2 text-center mb-3">
                <div class="achievement-badge locked">
                  <i class="fas fa-hiking fa-3x text-muted"></i>
                  <p class="small mt-2">First Steps</p>
                  <small class="text-muted">Complete 1 challenge</small>
                </div>
              </div>
              
              <div class="col-4 col-md-2 text-center mb-3">
                <div class="achievement-badge locked">
                  <i class="fas fa-bullseye fa-3x text-muted"></i>
                  <p class="small mt-2">Challenge Seeker</p>
                  <small class="text-muted">Complete 5 challenges</small>
                </div>
              </div>
              
              <div class="col-4 col-md-2 text-center mb-3">
                <div class="achievement-badge locked">
                  <i class="fas fa-trophy fa-3x text-muted"></i>
                  <p class="small mt-2">Challenge Master</p>
                  <small class="text-muted">Complete 10 challenges</small>
                </div>
              </div>
              
              <div class="col-4 col-md-2 text-center mb-3">
                <div class="achievement-badge locked">
                  <i class="fas fa-star fa-3x text-muted"></i>
                  <p class="small mt-2">Challenge Legend</p>
                  <small class="text-muted">Complete 25 challenges</small>
                </div>
              </div>
              
              <div class="col-4 col-md-2 text-center mb-3">
                <div class="achievement-badge locked">
                  <i class="fas fa-crown fa-3x text-muted"></i>
                  <p class="small mt-2">Challenge Champion</p>
                  <small class="text-muted">Complete 50 challenges</small>
                </div>
              </div>
              
              <div class="col-4 col-md-2 text-center mb-3">
                <div class="achievement-badge locked">
                  <i class="fas fa-map fa-3x text-muted"></i>
                  <p class="small mt-2">Explorer</p>
                  <small class="text-muted">Visit 10 places</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Settings -->
    <div class="row mb-5">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5><i class="fas fa-cog"></i> Settings</h5>
          </div>
          <div class="card-body">
            <div class="list-group">
              <a href="#" class="list-group-item list-group-item-action" onclick="return false;">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-bell"></i> Notifications
                  </div>
                  <i class="fas fa-chevron-right"></i>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="return false;">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-lock"></i> Privacy
                  </div>
                  <i class="fas fa-chevron-right"></i>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="return false;">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-language"></i> Language
                  </div>
                  <i class="fas fa-chevron-right"></i>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="return false;">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-palette"></i> Theme
                  </div>
                  <i class="fas fa-chevron-right"></i>
                </div>
              </a>
              <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <i class="fas fa-sign-out-alt"></i> Logout
                  </div>
                  <i class="fas fa-chevron-right"></i>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<style>
/* Additional styles for profile section */
.achievement-badge {
  padding: 15px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  transition: all 0.3s ease;
}

.achievement-badge:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-5px);
}

.achievement-badge.locked i {
  opacity: 0.3;
}

.achievement-badge.unlocked i {
  color: #cc9142 !important;
  animation: bounce 0.5s ease;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.challenge-item {
  border-left: 4px solid #cc9142;
  margin-bottom: 10px;
  transition: all 0.3s ease;
}

.challenge-item:hover {
  background: rgba(204, 145, 66, 0.1);
  transform: translateX(5px);
}

.challenge-item.completed {
  opacity: 0.7;
  border-left-color: #28a745;
}

#daily-challenges-list .list-group-item {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fdfdfd;
}

#recent-activity .list-group-item {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fdfdfd;
}

.profile-avatar {
  position: relative;
  display: inline-block;
}

.profile-avatar img {
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
</style>

<script>
function editProfile() {
  Swal.fire({
    title: 'Edit Profile',
    html: `
      <input id="username" class="swal2-input" placeholder="Username" value="<?php echo htmlspecialchars($display_name); ?>">
      <input id="email" class="swal2-input" placeholder="Email" type="email">
    `,
    showCancelButton: true,
    confirmButtonText: 'Save',
    confirmButtonColor: '#0b3842',
    preConfirm: () => {
      return {
        username: document.getElementById('username').value,
        email: document.getElementById('email').value
      }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        icon: 'success',
        title: 'Profile Updated!',
        confirmButtonColor: '#0b3842'
      });
    }
  });
}
</script>

<div class="tab" id="tab-game">
  <div class="container">
    <div class="row mb-5">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <h1 class="display-5 fw-bold">Challenge Wheel</h1>
          <p class="text-muted">Spin the wheel to unlock exciting challenges!</p>
        </div>
      </div>
    </div>

    <div class="wheel-box" id="wheel-box">
      <div class="wheel-wrapper" id="wheel-wrapper">
        <button id="spin">SPIN</button>
        <span class="arrow"></span>
        <div class="game-container">
          <div class="one">
           <h5> Visit a beach🌊 </h5>
          </div>

          <div class="two">
           <h5> Explore a local market🥥</h5>
          </div>

          <div class="three">
          <h5>  Hike to a waterfall💦</h5>
          </div>
          
          <div class="four">
          <h5>  Visit a historical site🏛️</h5>
          </div>

          <div class="five">
           <h5> Try a traditional dish🍽️</h5>
          </div>

          <div class="six">
           <h5> Buy a handmade souvenir🎁</h5>
          </div>

          <div class="seven">
          <h5>  Visit a viewpoint 👀</h5>
          </div>

          <div class="eight">
          <h5>  Watch the sunset 🌅</h5>
          </div>

        </div>

      </div>

    </div>


  </div>
</div>




  <!--BOTTOM NAV-->
  <div class="nav">
    <button onclick="switchTab('home')" class="tab-text"><span class="material-symbols-outlined">
        home
      </span></button>
    <button onclick="switchTab('place')" class="tab-text"><span class="material-symbols-outlined">
        location_on
      </span></button>
    <button onclick="switchTab('scan')" class="tab-text"><span class="material-symbols-outlined"><span class="material-symbols-outlined">
          qr_code_scanner
        </span></button>
    <button onclick="switchTab('profile')" class="tab-text"><span class="material-symbols-outlined">
        account_box
      </span></button>
    <button onclick="switchTab('game')" class="tab-text"><span class="material-symbols-outlined">
        target
      </span></button>
  </div>


  <script src="user_script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <script>
    const placesData =
      <?php
      mysqli_data_seek($places_result, 0);
      $places_array = [];
      while ($place = mysqli_fetch_assoc($places_result)) {
        $places_array[] = [
          'place_id' => $place['place_id'],
          'name' => htmlspecialchars($place['name']),
          'description' => htmlspecialchars($place['description']),
          'main_image' => htmlspecialchars($place['main_image']),
          'latitude' => $place['latitude'],
          'longitude' => $place['longitude']
        ];
      }
      echo json_encode($places_array);
      ?>;
  </script>
</body>

</html>