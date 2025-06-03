<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Learn from YouTube by searching </title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f9fafb;
    margin: 0;
    padding: 30px;
    color: #222;
  }
  h1 {
    text-align: center;
    margin-bottom: 30px;
    color: #1a73e8;
  }
  form {
    max-width: 600px;
    margin: 0 auto 40px;
    display: flex;
    gap: 10px;
  }
  input[type="text"] {
    flex-grow: 1;
    padding: 12px 15px;
    font-size: 1.1em;
    border-radius: 8px;
    border: 2px solid #1a73e8;
  }
  input[type="text"]:focus {
    outline: none;
    border-color: #155ab6;
  }
  button {
    padding: 12px 22px;
    font-size: 1.1em;
    border-radius: 8px;
    border: none;
    background-color: #1a73e8;
    color: white;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #155ab6;
  }

  .videos-container {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    gap: 25px;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  }

  .video-card iframe {
    width: 100%;
    height: 180px;
    border-radius: 12px;
    border: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  .video-title {
    margin: 10px 0 5px;
    font-weight: 600;
    color: #333;
    font-size: 1em;
  }
  .video-channel {
    color: #666;
    font-size: 0.9em;
  }

  .no-results {
    text-align: center;
    color: #999;
    font-style: italic;
    font-size: 1.1em;
  }
  body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
  margin: 0;
  padding: 30px;
  color: #222;
}
.back-button, .prev-button {
    display: inline-block;
    margin: 0 10px 30px 0;
    padding: 10px 18px;
    background-color: #00796b;
    color: white;
    font-weight: 600;
    border-radius: 6px;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(0, 121, 107, 0.4);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.back-button:hover, .prev-button:hover {
    background-color: #004d40;
    box-shadow: 0 6px 18px rgba(0, 77, 64, 0.6);
}


</style>
</head>
<body>
<a href="javascript:history.back()" class="back-button">&larr; Back</a>
<h1>Learn from YouTube by searching for the lecturer u are in </h1>

<form id="searchForm">
  <input type="text" id="searchInput" placeholder="Search lessons by lecturer or topic" required />
  <button type="submit">Search</button>
</form>

<div id="videos" class="videos-container"></div>
<p id="noResults" class="no-results" style="display:none;">No videos found.</p>

<script>
  // Insert your actual API key here
  const apiKey = 'AIzaSyA739pUGgnqwpXUxOXtMsDKq5gEJVCBHJ4';
  const searchForm = document.getElementById('searchForm');
  const searchInput = document.getElementById('searchInput');
  const videosContainer = document.getElementById('videos');
  const noResultsText = document.getElementById('noResults');

  searchForm.addEventListener('submit', e => {
    e.preventDefault();
    const query = searchInput.value.trim();
    if (!query) return;
    videosContainer.innerHTML = '';
    noResultsText.style.display = 'none';
    fetchVideos(query);
  });

  async function fetchVideos(query) {
    const endpoint = `https://www.googleapis.com/youtube/v3/search?part=snippet&type=video&maxResults=12&q=${encodeURIComponent(query)}&key=${apiKey}`;
    try {
      const response = await fetch(endpoint);
      const data = await response.json();

      if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
          const videoId = item.id.videoId;
          const title = item.snippet.title;
          const channel = item.snippet.channelTitle;
          const videoCard = document.createElement('div');
          videoCard.classList.add('video-card');
          videoCard.innerHTML = `
            <iframe 
              src="https://www.youtube.com/embed/${videoId}" 
              allowfullscreen
              loading="lazy"
              title="${title}"
            ></iframe>
            <div class="video-title">${title}</div>
            <div class="video-channel">Channel: ${channel}</div>
          `;
          videosContainer.appendChild(videoCard);
        });
      } else {
        noResultsText.style.display = 'block';
      }
    } catch (error) {
      noResultsText.textContent = 'Error fetching videos, please try again later.';
      noResultsText.style.display = 'block';
      console.error('YouTube API error:', error);
    }
  }
</script>

</body>
</html>
