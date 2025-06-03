<?php
session_start();

class StudySitesLibrary {
    private $sites = [];

    public function __construct() {
        $this->sites = [
            [
                'name' => 'Khan Academy',
                'url' => 'https://www.khanacademy.org/',
                'description' => 'Free online courses, lessons, and practice in math, science, and more.',
                'category' => 'General'
            ],
            [
                'name' => 'Coursera',
                'url' => 'https://www.coursera.org/',
                'description' => 'Online courses from top universities and companies worldwide.',
                'category' => 'University-level'
            ],
            [
                'name' => 'edX',
                'url' => 'https://www.edx.org/',
                'description' => 'Access to high-quality education from the world’s best universities.',
                'category' => 'University-level'
            ],
            [
                'name' => 'Udemy',
                'url' => 'https://www.udemy.com/',
                'description' => 'Online learning platform with a vast range of courses.',
                'category' => 'General'
            ],
            [
                'name' => 'Quizlet',
                'url' => 'https://quizlet.com/',
                'description' => 'Study tools and flashcards for various subjects.',
                'category' => 'Flashcards & Practice'
            ],
            [
                'name' => 'Codecademy',
                'url' => 'https://www.codecademy.com/',
                'description' => 'Learn coding and programming interactively.',
                'category' => 'Programming'
            ],
            [
                'name' => 'Duolingo',
                'url' => 'https://www.duolingo.com/',
                'description' => 'Free language learning platform with gamified lessons.',
                'category' => 'Languages'
            ],
            // Added more pro research/study sites:
            [
                'name' => 'Google Scholar',
                'url' => 'https://scholar.google.com/',
                'description' => 'Search scholarly literature, including theses, books, abstracts and articles.',
                'category' => 'Research'
            ],
            [
                'name' => 'JSTOR',
                'url' => 'https://www.jstor.org/',
                'description' => 'Access thousands of academic journals, books, and primary sources.',
                'category' => 'Research'
            ],
            [
                'name' => 'ResearchGate',
                'url' => 'https://www.researchgate.net/',
                'description' => 'Professional network for researchers to share papers and results.',
                'category' => 'Research'
            ],
            [
                'name' => 'MIT OpenCourseWare',
                'url' => 'https://ocw.mit.edu/',
                'description' => 'Free course materials from MIT covering many subjects.',
                'category' => 'University-level'
            ],
            [
                'name' => 'Wolfram Alpha',
                'url' => 'https://www.wolframalpha.com/',
                'description' => 'Computational knowledge engine and answer engine.',
                'category' => 'Tools & Calculators'
            ],
            [
                'name' => 'Project Gutenberg',
                'url' => 'https://www.gutenberg.org/',
                'description' => 'Over 60,000 free eBooks, mainly classic literature.',
                'category' => 'Reading & Literature'
            ],
            [
                'name' => 'TED-Ed',
                'url' => 'https://ed.ted.com/',
                'description' => 'Educational videos and lessons created by educators and animators.',
                'category' => 'General'
            ],
        ];
    }

    public function getAllSites() {
        return $this->sites;
    }

    public function getSitesByCategory($category) {
        if (!$category) return $this->getAllSites();
        $filtered = [];
        foreach ($this->sites as $site) {
            if (strcasecmp($site['category'], $category) === 0) {
                $filtered[] = $site;
            }
        }
        return $filtered;
    }

    public function searchSitesByName($keyword) {
        if (!$keyword) return $this->getAllSites();
        $filtered = [];
        foreach ($this->sites as $site) {
            if (stripos($site['name'], $keyword) !== false) {
                $filtered[] = $site;
            }
        }
        return $filtered;
    }

    public function getCategories() {
        $categories = [];
        foreach ($this->sites as $site) {
            $categories[] = $site['category'];
        }
        return array_unique($categories);
    }
}

$library = new StudySitesLibrary();

$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// First filter by category if set
$sites = $library->getSitesByCategory($category);

// Then filter by search keyword
if ($search) {
    $sites = array_filter($sites, function($site) use ($search) {
        return stripos($site['name'], $search) !== false;
    });
}

$categories = $library->getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Online Study Sites Library</title>
<style>
    /* Reset & base */
    * {
        box-sizing: border-box;
    }
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px 60px;
        background: #f4f7fa;
        color: #333;
        line-height: 1.6;
    }
    h1 {
        text-align: center;
        margin-bottom: 30px;
        font-weight: 700;
        font-size: 2.5rem;
        color: #222;
        letter-spacing: 1px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    form {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        margin-bottom: 40px;
    }
    select, input[type="text"] {
        padding: 12px 15px;
        font-size: 1.1rem;
        border: 1.8px solid #ccc;
        border-radius: 6px;
        transition: border-color 0.3s ease;
        min-width: 180px;
    }
    select:focus, input[type="text"]:focus {
        outline: none;
        border-color: #007BFF;
        box-shadow: 0 0 8px #007BFFaa;
    }
    input[type="submit"] {
        background-color: #007BFF;
        border: none;
        color: white;
        font-weight: 600;
        cursor: pointer;
        border-radius: 6px;
        padding: 12px 28px;
        font-size: 1.1rem;
        transition: background-color 0.3s ease;
        min-width: 120px;
    }
    input[type="submit"]:hover {
        background-color: #0056b3;
    }

    .site {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        padding: 25px 30px;
        transition: box-shadow 0.3s ease;
    }
    .site:hover {
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .site h2 {
        margin: 0 0 12px 0;
        font-size: 1.6rem;
        color: #0056b3;
    }
    .site h2 a {
        text-decoration: none;
        color: inherit;
        transition: color 0.3s ease;
    }
    .site h2 a:hover {
        color: #ff6600;
        text-decoration: underline;
    }
    .site p {
        margin: 8px 0;
        font-size: 1rem;
        color: #555;
    }
    .site p strong {
        color: #222;
    }

    .no-results {
        text-align: center;
        font-style: italic;
        color: #999;
        font-size: 1.3rem;
        margin-top: 80px;
    }

    /* Responsive */
    @media (max-width: 600px) {
        form {
            flex-direction: column;
            align-items: center;
        }
        select, input[type="text"], input[type="submit"] {
            min-width: 100%;
        }
        body {
            margin: 20px 10px 40px;
        }
    }
    /* Reset some default browser styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #fff);
    color: #333;
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px 60px;
    line-height: 1.6;
}

h1 {
    font-weight: 700;
    font-size: 2.8rem;
    color: #00796b;
    text-align: center;
    margin-bottom: 40px;
    text-shadow: 1px 1px 3px rgba(0, 121, 107, 0.3);
}

form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
    background: #ffffffcc;
    padding: 20px 25px;
    border-radius: 10px;
    box-shadow: 0 6px 15px rgba(0, 121, 107, 0.15);
}

select, input[type="text"] {
    flex: 1 1 220px;
    min-width: 180px;
    padding: 12px 15px;
    font-size: 1.1rem;
    border: 2px solid #00796b;
    border-radius: 8px;
    transition: border-color 0.3s ease;
    color: #004d40;
    background-color: #e0f2f1;
    font-weight: 600;
}

select:focus, input[type="text"]:focus {
    outline: none;
    border-color: #004d40;
    background-color: #b2dfdb;
}

input[type="submit"] {
    background: #00796b;
    border: none;
    color: white;
    font-size: 1.15rem;
    font-weight: 700;
    padding: 12px 28px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 121, 107, 0.4);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    flex-shrink: 0;
}

input[type="submit"]:hover {
    background-color: #004d40;
    box-shadow: 0 6px 18px rgba(0, 77, 64, 0.6);
}

.site {
    background: white;
    border-radius: 12px;
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
    border-left: 8px solid #00796b;
}

.site:hover {
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.15);
}

.site h2 {
    font-size: 1.8rem;
    color: #004d40;
    margin-bottom: 10px;
    font-weight: 700;
}

.site h2 a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}

.site h2 a:hover {
    color: #00796b;
    text-decoration: underline;
}

.site p {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #555;
}

.site p strong {
    color: #00796b;
}

.no-results {
    text-align: center;
    color: #999;
    font-style: italic;
    font-size: 1.3rem;
    margin-top: 60px;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    form {
        flex-direction: column;
        gap: 12px;
        padding: 15px 20px;
    }

    input[type="submit"] {
        width: 100%;
        padding: 14px;
        font-size: 1.2rem;
    }

    .site {
        padding: 20px 20px;
    }
}
/* Reset some default browser styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #fff);
    color: #333;
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px 60px;
    line-height: 1.6;
}

h1 {
    font-weight: 700;
    font-size: 2.8rem;
    color: #00796b;
    text-align: center;
    margin-bottom: 40px;
    text-shadow: 1px 1px 3px rgba(0, 121, 107, 0.3);
}

form {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-bottom: 40px;
    background: #ffffffcc;
    padding: 20px 25px;
    border-radius: 10px;
    box-shadow: 0 6px 15px rgba(0, 121, 107, 0.15);
}

select, input[type="text"] {
    flex: 1 1 220px;
    min-width: 180px;
    padding: 12px 15px;
    font-size: 1.1rem;
    border: 2px solid #00796b;
    border-radius: 8px;
    transition: border-color 0.3s ease;
    color: #004d40;
    background-color: #e0f2f1;
    font-weight: 600;
}

select:focus, input[type="text"]:focus {
    outline: none;
    border-color: #004d40;
    background-color: #b2dfdb;
}

input[type="submit"] {
    background: #00796b;
    border: none;
    color: white;
    font-size: 1.15rem;
    font-weight: 700;
    padding: 12px 28px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 121, 107, 0.4);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    flex-shrink: 0;
}

input[type="submit"]:hover {
    background-color: #004d40;
    box-shadow: 0 6px 18px rgba(0, 77, 64, 0.6);
}

.site {
    background: white;
    border-radius: 12px;
    padding: 25px 30px;
    margin-bottom: 25px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease;
    border-left: 8px solid #00796b;
}

.site:hover {
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.15);
}

.site h2 {
    font-size: 1.8rem;
    color: #004d40;
    margin-bottom: 10px;
    font-weight: 700;
}

.site h2 a {
    text-decoration: none;
    color: inherit;
    transition: color 0.3s ease;
}

.site h2 a:hover {
    color: #00796b;
    text-decoration: underline;
}

.site p {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #555;
}

.site p strong {
    color: #00796b;
}

.no-results {
    text-align: center;
    color: #999;
    font-style: italic;
    font-size: 1.3rem;
    margin-top: 60px;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    form {
        flex-direction: column;
        gap: 12px;
        padding: 15px 20px;
    }

    input[type="submit"] {
        width: 100%;
        padding: 14px;
        font-size: 1.2rem;
    }

    .site {
        padding: 20px 20px;
    }
}
.back-button {
    display: inline-block;
    margin-bottom: 30px;
    padding: 10px 18px;
    background-color: #00796b;
    color: white;
    font-weight: 600;
    border-radius: 6px;
    text-decoration: none;
    box-shadow: 0 4px 10px rgba(0, 121, 107, 0.4);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.back-button:hover {
    background-color: #004d40;
    box-shadow: 0 6px 18px rgba(0, 77, 64, 0.6);
}

</style>
</head>
<body>
<a href="javascript:history.back()" class="back-button">&larr; Back</a>
<h1>Online Study Sites Library</h1>



<form method="get" action="">
    <select name="category" aria-label="Filter by category">
        <option value="">-- All Categories --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($cat === $category) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input
        type="text"
        name="search"
        placeholder="Search by site name"
        value="<?= htmlspecialchars($search) ?>"
        aria-label="Search study sites by name"
    />

    <input type="submit" value="Filter" aria-label="Filter study sites" />
</form>

<?php if (count($sites) === 0): ?>
    <p class="no-results">No study sites found matching your criteria.</p>
<?php else: ?>
    <?php foreach ($sites as $site): ?>
        <article class="site" role="region" aria-labelledby="site-<?= md5($site['name']) ?>">
            <h2 id="site-<?= md5($site['name']) ?>">
                <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" rel="noopener noreferrer">
                    <?= htmlspecialchars($site['name']) ?>
                </a>
            </h2>
            <p><?= htmlspecialchars($site['description']) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($site['category']) ?></p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
