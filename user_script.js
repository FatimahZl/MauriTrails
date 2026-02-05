//----SWITCH TABS-----
function switchTab(name){
  document.querySelectorAll('.tab').forEach(t=> t.classList.add('hidden'));
  const target = document.getElementById('tab-'+name);
  if (target) {
    target.classList.remove('hidden');
    

    if (name === 'map') {
      setTimeout(() => {
        if (!window.mapInitialized) {
          initMap();
          window.mapInitialized = true;
        } else {
          map.invalidateSize();
        }
      }, 100);
    }
  }
}

window.switchTab = switchTab;
switchTab('home');


function showPlaceDetails(place) {

    document.getElementById('modalPlaceName').innerText = place.name;
    document.getElementById('modalTitleText').innerText = place.name;
    document.getElementById('modalDescription').innerText = place.description;
    document.getElementById('modalImage').src = "uploads/images/" + place.image;
    

    document.getElementById('modalHistory').innerText = place.history || "No history available.";
    document.getElementById('modalCategory').innerText = place.category;
    document.getElementById('modalAddress').innerText = place.address;
    document.getElementById('modalVisitingHours').innerText = place.visiting_hours || "Not specified";
    

    const fee = place.entry_fee == 0 ? "Free" : "Rs " + place.entry_fee;
    document.getElementById('modalEntryFee').innerText = fee;
    
    document.getElementById('modalWebsite').innerText = place.website || "N/A";


    const dirBtn = document.getElementById('modalDirectionBtn');
    dirBtn.onclick = function() {
        $('#placeDetailsModal').modal('hide');
        switchTab('map');
        getDirections(place.latitude, place.longitude);
    };


    var myModal = new bootstrap.Modal(document.getElementById('placeDetailsModal'));
    myModal.show();
}


function onScanSuccess(qrMessage) {
    console.log(`🔍 QR Code Scanned: ${qrMessage}`);
    

    html5QrcodeScanner.clear();
    

    let redirectUrl = qrMessage;
    
    if (qrMessage.includes('view_place_details.php')) {

        const urlParams = new URLSearchParams(qrMessage.split('?')[1]);
        const placeId = urlParams.get('place_id');
        
        if (placeId) {
            console.log(`📍 Extracted Place ID: ${placeId}`);

            const currentPath = window.location.pathname;
            const directory = currentPath.substring(0, currentPath.lastIndexOf('/'));
            redirectUrl = window.location.origin + directory + '/view_place_details.php?place_id=' + placeId;
        }
    } else if (qrMessage.startsWith('http://') || qrMessage.startsWith('https://')) {

        redirectUrl = qrMessage;
    } else {

        console.log('QR code format not recognized, trying direct use');
    }
    
    console.log(`🚀 Redirecting to: ${redirectUrl}`);
    

    window.location.href = redirectUrl;
}

function onScanError(errorMessage) {

    console.log(`QR Scan Error: ${errorMessage}`);
}


let html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", 
    { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0,
        disableFlip: false
    }
);

html5QrcodeScanner.render(onScanSuccess, onScanError);


//----MAP TAB-----

let map, userMarker, routingControl;
let pendingDestination = null;
let placeMarkers = []; 

function initMap() {

    if (!map) {
        map = L.map('map').setView([-20.3484, 57.5522], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        

        addPlacesToMap();
        

        startTracking();
    }
}

function addPlacesToMap() {
    if (typeof placesData === 'undefined') {
        console.warn('No places data available');
        return;
    }
    
    placesData.forEach(place => {
        if (place.latitude && place.longitude) {

            const marker = L.marker([place.latitude, place.longitude], {
                icon: L.icon({
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                })
            }).addTo(map);
   
            const popupContent = `
                <div style="min-width: 200px;">
                    <img src="uploads/images/${place.image}" 
                         style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;" 
                         alt="${place.name}">
                    <h4 style="margin: 0 0 8px 0; color: #333; font-size: 16px; font-weight: 600;">
                        ${place.name}
                    </h4>
                    <p style="margin: 0 0 10px 0; color: #666; font-size: 13px; line-height: 1.4;">
                        ${place.description.substring(0, 100)}...
                    </p>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="window.location.href='view_place_details.php?place_id=${place.place_id}'" 
                                style="flex: 1; padding: 8px 12px; background: #2d5760; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                            View Details
                        </button>
                        <button onclick="getDirections(${place.latitude}, ${place.longitude})" 
                                style="flex: 1; padding: 8px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                            Get Directions
                        </button>
                    </div>
                </div>
            `;
            
            marker.bindPopup(popupContent, {
                maxWidth: 300,
                className: 'custom-popup'
            });
            
            placeMarkers.push(marker);
        }
    });
}

function startTracking(){
  if(navigator.geolocation){
    navigator.geolocation.watchPosition(success, error, {
      enableHighAccuracy: true,
      timeout: 5000,
      maximumAge: 0
    });
  } else {
    alert('Geolocation is not supported by your browser');
  }
}

function success(position){
  const latitude = position.coords.latitude;
  const longitude = position.coords.longitude;

  if(!userMarker){
    userMarker = L.marker([latitude, longitude], {
      icon: L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      })
    }).addTo(map)
      .bindPopup("You are here")
      .openPopup();

    map.setView([latitude, longitude], 13);
    

    if (pendingDestination) {
      drawRoute(latitude, longitude, pendingDestination.lat, pendingDestination.lng);
      pendingDestination = null;
    }
  } else {
    userMarker.setLatLng([latitude, longitude]);
  }
}

function error(err) {
  console.warn('Geolocation error:', err.message);
  alert('Could not get your location. Please enable location services.');
}

function drawRoute(startLat, startLng, destLat, destLng) {

  if (routingControl) {
    map.removeControl(routingControl);
  }

  routingControl = L.Routing.control({
    waypoints: [
      L.latLng(startLat, startLng),
      L.latLng(destLat, destLng)
    ],
    routeWhileDragging: true,
    lineOptions: {
      styles: [{ color: '#3498db', weight: 6 }]
    },
    createMarker: function() {
      return null;
    }
  }).addTo(map);
  

  map.fitBounds([
    [startLat, startLng],
    [destLat, destLng]
  ], { padding: [50, 50] });
}

function getDirections(destinationLat, destinationLong) {
  console.log('getDirections called with:', destinationLat, destinationLong);
  

  if (!window.mapInitialized) {
    switchTab('map');
    setTimeout(() => {
      getDirections(destinationLat, destinationLong);
    }, 500);
    return;
  }

  if (!userMarker) {

    pendingDestination = { lat: destinationLat, lng: destinationLong };
    alert("Getting your location... Route will display shortly.");
    return;
  }


  const userLat = userMarker.getLatLng().lat;
  const userLng = userMarker.getLatLng().lng;
  drawRoute(userLat, userLng, destinationLat, destinationLong);
}


window.getDirections = getDirections;


// Wheel Spin Functionality
let wheelRotation = 0;
let isSpinning = false;

const challenges = [
  { name: "Visit a beach", icon: "🌊", points: 50, category: "beach" },
  { name: "Explore a local market", icon: "🥥", points: 40, category: "market" },
  { name: "Hike to a waterfall", icon: "💦", points: 60, category: "waterfall" },
  { name: "Visit a historical site", icon: "🏛️", points: 70, category: "historical" },
  { name: "Try a traditional dish", icon: "🍽️", points: 30, category: "food" },
  { name: "Buy a handmade souvenir", icon: "🎁", points: 40, category: "shopping" },
  { name: "Visit a viewpoint", icon: "👀", points: 50, category: "viewpoint" },
  { name: "Watch the sunset", icon: "🌅", points: 45, category: "sunset" }
];

function initializeWheelSpin() {
  const spinButton = document.getElementById('spin');
  const gameContainer = document.querySelector('.game-container');
  
  if (!spinButton || !gameContainer) return;
  
  spinButton.addEventListener('click', function() {
    if (isSpinning) return;
    
    // Check if user already has a daily challenge
    fetch('check_daily_challenge.php')
      .then(response => response.json())
      .then(data => {
        if (data.has_challenge) {
          Swal.fire({
            icon: 'info',
            title: 'Active Challenge',
            text: 'You already have an active daily challenge! Complete it first.',
            confirmButtonColor: '#0b3842'
          });
          return;
        }
        
        spinWheel(gameContainer, spinButton);
      });
  });
}

function spinWheel(gameContainer, spinButton) {
  isSpinning = true;
  spinButton.disabled = true;
  
  // Random spins between 5-10 full rotations plus random angle
  const extraRotations = Math.floor(Math.random() * 6) + 5;
  const randomDegree = Math.floor(Math.random() * 360);
  const totalRotation = (extraRotations * 360) + randomDegree;
  
  wheelRotation += totalRotation;
  
  gameContainer.style.transition = 'transform 4s cubic-bezier(0.15, 0, 0.15, 1)';
  gameContainer.style.transform = `rotate(${wheelRotation}deg)`;
  
  setTimeout(() => {
    const normalizedRotation = wheelRotation % 360;
    const segmentAngle = 360 / 8;
    const selectedIndex = Math.floor((360 - normalizedRotation + (segmentAngle / 2)) / segmentAngle) % 8;
    
    const selectedChallenge = challenges[selectedIndex];
    
    // Save the challenge to database
    saveDailyChallenge(selectedChallenge);
    
    isSpinning = false;
    spinButton.disabled = false;
  }, 4000);
}

function saveDailyChallenge(challenge) {
  fetch('save_daily_challenge.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      challenge_name: challenge.name,
      challenge_icon: challenge.icon,
      challenge_category: challenge.category,
      points_reward: challenge.points
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Daily Challenge!',
        html: `
          <div style="font-size: 48px; margin: 20px 0;">${challenge.icon}</div>
          <h3>${challenge.name}</h3>
          <p>Complete this challenge to earn <strong>${challenge.points} points</strong>!</p>
          <p style="color: #666; font-size: 14px;">Check your profile to track progress.</p>
        `,
        confirmButtonText: 'View Challenge',
        confirmButtonColor: '#0b3842',
        showCancelButton: true,
        cancelButtonText: 'Close'
      }).then((result) => {
        if (result.isConfirmed) {
          switchTab('profile');
          loadDailyChallenges();
        }
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Oops!',
        text: data.message || 'Failed to save challenge',
        confirmButtonColor: '#0b3842'
      });
    }
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An error occurred. Please try again.',
      confirmButtonColor: '#0b3842'
    });
  });
}

// Profile Functions
function loadUserProfile() {
  fetch('get_user_profile.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update stats
        document.getElementById('places-visited').textContent = data.places_visited || 0;
        document.getElementById('total-points').textContent = data.total_points || 0;
        document.getElementById('qr-scanned').textContent = data.qr_scanned || 0;
        document.getElementById('achievements').textContent = data.achievements || 0;
        
        // Update level display
        const userLevelElement = document.getElementById('user-level');
        if (userLevelElement) {
          userLevelElement.textContent = data.level || 1;
        }
        
        const currentLevelElement = document.getElementById('current-level');
        if (currentLevelElement) {
          currentLevelElement.textContent = data.level || 1;
        }
        
        // Update XP display
        const currentXpElement = document.getElementById('current-xp');
        const neededXpElement = document.getElementById('needed-xp');
        if (currentXpElement && neededXpElement && data.level_progress) {
          currentXpElement.textContent = data.level_progress.current;
          neededXpElement.textContent = data.level_progress.needed;
        }
        
        // Update progress bar
        const progressBar = document.getElementById('level-progress-bar');
        if (progressBar && data.level_progress) {
          const percentage = (data.level_progress.current / data.level_progress.needed) * 100;
          progressBar.style.width = `${percentage}%`;
          progressBar.textContent = `${Math.round(percentage)}%`;
          progressBar.setAttribute('aria-valuenow', percentage);
        }
      }
    })
    .catch(error => console.error('Error loading profile:', error));
}

function loadDailyChallenges() {
  fetch('get_daily_challenges.php')
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById('daily-challenges-list');
      if (!container) return;
      
      if (data.success && data.challenges.length > 0) {
        container.innerHTML = data.challenges.map(challenge => `
          <div class="list-group-item challenge-item ${challenge.status === 'completed' ? 'completed' : ''}">
            <div class="d-flex justify-content-between align-items-start">
              <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                  <span style="font-size: 24px; margin-right: 12px;">${challenge.challenge_icon}</span>
                  <div>
                    <h6 class="mb-1">${challenge.challenge_name}</h6>
                    <small class="text-muted">Reward: ${challenge.points_reward} points</small>
                  </div>
                </div>
                ${challenge.status === 'active' ? `
                  <div class="mt-2">
                    <small class="text-muted">Expires: ${formatDate(challenge.expires_at)}</small>
                  </div>
                ` : ''}
              </div>
              <div class="text-end">
                ${challenge.status === 'active' ? `
                  <button class="btn btn-sm btn-success" onclick="completeChallenge(${challenge.challenge_id})">
                    Complete
                  </button>
                ` : `
                  <span class="badge bg-success">
                    <i class="fas fa-check"></i> Completed
                  </span>
                `}
              </div>
            </div>
          </div>
        `).join('');
      } else {
        container.innerHTML = `
          <div class="text-center py-4">
            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
            <p class="text-muted">No daily challenges yet!</p>
            <button class="btn btn-primary" onclick="switchTab('game')">
              Spin the Wheel
            </button>
          </div>
        `;
      }
    })
    .catch(error => console.error('Error loading challenges:', error));
}

function completeChallenge(challengeId) {
  Swal.fire({
    title: 'Complete Challenge?',
    text: 'Have you completed this challenge?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, I completed it!',
    cancelButtonText: 'Not yet',
    confirmButtonColor: '#0b3842'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('complete_challenge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ challenge_id: challengeId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Challenge Completed!',
            text: `You earned ${data.points_earned} points!`,
            confirmButtonColor: '#0b3842'
          });
          loadDailyChallenges();
          loadUserProfile();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Oops!',
            text: data.message,
            confirmButtonColor: '#0b3842'
          });
        }
      });
    }
  });
}

function formatDate(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diff = date - now;
  const hours = Math.floor(diff / (1000 * 60 * 60));
  
  if (hours < 24) {
    return `${hours} hours`;
  } else {
    const days = Math.floor(hours / 24);
    return `${days} day${days > 1 ? 's' : ''}`;
  }
}

// Load recent activities
function loadRecentActivities() {
  fetch('get_recent_activities.php')
    .then(response => response.json())
    .then(data => {
      const container = document.getElementById('recent-activity');
      if (!container) return;
      
      if (data.success && data.activities.length > 0) {
        container.innerHTML = data.activities.map(activity => `
          <div class="list-group-item">
            <div class="d-flex justify-content-between">
              <div>
                <h6 class="mb-1">${activity.description}</h6>
                <small class="text-muted">${activity.timestamp}</small>
              </div>
              <span class="badge bg-warning">+${activity.points} pts</span>
            </div>
          </div>
        `).join('');
      } else {
        container.innerHTML = '<p class="text-muted text-center">No recent activity</p>';
      }
    });
}

// Initialize profile tab when switched
document.addEventListener('DOMContentLoaded', function() {
  const profileTab = document.getElementById('tab-profile');
  if (profileTab) {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (!profileTab.classList.contains('hidden')) {
          loadUserProfile();
          loadDailyChallenges();
          loadRecentActivities();
        }
      });
    });
    
    observer.observe(profileTab, { attributes: true, attributeFilter: ['class'] });
  }
});