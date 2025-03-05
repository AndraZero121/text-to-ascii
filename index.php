<?php
// Define classes at the top of the file
class ShirokoCode
{
  private $codeMap = ["0" => "\u{200B}", "1" => "\u{200C}", "\u{200B}" => "0", "\u{200C}" => "1"];

  private function sanitizeInput($input)
  {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
  }

  public function textToShirokocode($text)
  {
    if (empty($text)) {
      throw new Exception('Text cannot be empty');
    }
    $text = $this->sanitizeInput($text);
    return implode(" ", array_map(fn($char) => "Nn" . implode("", array_map(fn($bit) => $this->codeMap[$bit] ?? '', str_split(str_pad(decbin(mb_ord($char, 'UTF-8')), 8, '0', STR_PAD_LEFT)))), mb_str_split($text, 1, 'UTF-8'))) . " Sensei...";
  }

  public function shirokoCodeToReadable($shirokocode)
  {
    if (empty($shirokocode)) {
      throw new Exception('Shiroko ASCII code cannot be empty');
    }
    $shirokocode = $this->sanitizeInput($shirokocode);
    $shirokocode = html_entity_decode($shirokocode, ENT_QUOTES, 'UTF-8');
    $codes = preg_grep('/^Nn/', explode(" ", $shirokocode));
    if (empty($codes)) {
      throw new Exception('Invalid Shiroko ASCII code format');
    }
    return implode("", array_map(fn($code) => mb_chr(bindec(implode("", array_map(fn($char) => $this->codeMap[$char] ?? '', mb_str_split(substr($code, 2), 1, 'UTF-8')))), 'UTF-8'), $codes));
  }

  public function generateRandomShirokocode($length = 10)
  {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randomText = '';
    for ($i = 0; $i < $length; $i++) {
      $randomText .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $this->textToShirokocode($randomText);
  }
}

class ConversionHistory
{
  private $history = [];
  private $maxEntries = 10;

  public function __construct()
  {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $this->loadHistory();
  }

  private function loadHistory()
  {
    $this->history = $_SESSION['conversion_history'] ?? [];

    // Load from cookies if session is empty
    if (empty($this->history) && isset($_COOKIE['conversionHistory'])) {
      $this->history = json_decode($_COOKIE['conversionHistory'], true) ?? [];
    }
  }

  public function addEntry($type, $input, $output)
  {
    $entry = [
      'type' => $type,
      'input' => $input,
      'output' => $output,
      'timestamp' => date('Y-m-d H:i:s')
    ];

    array_unshift($this->history, $entry);
    $this->history = array_slice($this->history, 0, $this->maxEntries);
    $this->saveHistory();

    // Save last conversion state to cookies
    setcookie('lastDirection', $type, time() + (86400 * 7), '/');
    setcookie('lastInput', $input, time() + (86400 * 7), '/');
    setcookie('lastOutput', $output, time() + (86400 * 7), '/');
  }

  public function clear()
  {
    $this->history = [];
    $this->saveHistory();
    setcookie('conversionHistory', '', time() - 3600, '/');
    setcookie('lastDirection', '', time() - 3600, '/');
    setcookie('lastInput', '', time() - 3600, '/');
    setcookie('lastOutput', '', time() - 3600, '/');
  }

  public function getHistory()
  {
    return $this->history;
  }

  private function saveHistory()
  {
    $_SESSION['conversion_history'] = $this->history;
    $jsonHistory = json_encode($this->history);
    if ($jsonHistory === false) {
      throw new Exception('Failed to encode history data');
    }
    setcookie('conversionHistory', $jsonHistory, time() + (86400 * 7), '/');
  }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');

  $shiroko = new ShirokoCode();
  $history = new ConversionHistory();

  try {
    $result = match ($_POST['action']) {
      'textToShirokocode' => $shiroko->textToShirokocode($_POST['text'] ?? ''),
      'shirokocodeToText' => $shiroko->shirokoCodeToReadable($_POST['shirokocode'] ?? ''),
      'generateRandom' => $shiroko->generateRandomShirokocode(),
      'clearHistory' => null,
      default => throw new Exception('Invalid Request')
    };

    if ($_POST['action'] === 'clearHistory') {
      $history->clear();
    } elseif ($_POST['action'] === 'textToShirokocode' || $_POST['action'] === 'shirokocodeToText') {
      $history->addEntry(
        $_POST['action'],
        $_POST['action'] === 'textToShirokocode' ? $_POST['text'] : $_POST['shirokocode'],
        $result
      );
    }

    echo json_encode([
      'status' => 'success',
      'result' => $result,
      'history' => $history->getHistory()
    ]);
  } catch (Exception $e) {
    echo json_encode([
      'status' => 'error',
      'message' => $e->getMessage()
    ]);
  }
  exit;
}

// Get initial state from cookies
$lastDirection = $_COOKIE['lastDirection'] ?? 'textToShirokocode';
$lastInput = $_COOKIE['lastInput'] ?? '';
$lastOutput = $_COOKIE['lastOutput'] ?? '';
$history = new ConversionHistory();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shiroko ASCII Converter Pro</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Source+Code+Pro:wght@300;400;600&display=swap"
    rel="stylesheet">
  <link rel="icon" href="https://pbs.twimg.com/profile_images/1575119130293768192/wYFj1kYZ_400x400.jpg">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.7) 0%, rgba(255, 255, 255, 0.7) 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: -1;
      background-image: url('https://media1.tenor.com/m/bLEBFmAZaqQAAAAC/shiroko-dance.gif');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      opacity: 0.3;
    }

    .converter-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
      border-radius: 15px;
      transition: transform 0.3s ease;
    }

    .code-font {
      font-family: 'Source Code Pro', monospace;
    }

    .converter-card:hover {
      transform: scale(1.02);
    }

    .btn-glow {
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .btn-glow:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
    }

    .arrow-button {
      transition: transform 0.3s ease, color 0.3s ease;
    }

    .arrow-button:hover {
      transform: scale(1.2);
      color: #3b82f6;
    }

    .history-item {
      transition: all 0.3s ease;
    }

    .history-item:hover {
      background-color: #f0f4f8;
      transform: scale(1.02);
    }

    .tooltip {
      position: relative;
      display: inline-block;
    }

    .tooltip .tooltiptext {
      visibility: hidden;
      width: 120px;
      background-color: #555;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px;
      position: absolute;
      z-index: 1;
      bottom: 125%;
      left: 50%;
      margin-left: -60px;
      opacity: 0;
      transition: opacity 0.3s;
    }

    .tooltip:hover .tooltiptext {
      visibility: visible;
      opacity: 1;
    }

    .bg-white {
      background: rgba(255, 255, 255, 0.9) !important;
      backdrop-filter: blur(5px);
    }

    .converter-card textarea {
      transition: all 0.3s ease;
      backdrop-filter: none;
      filter: none;
      background: rgba(255, 255, 255, 0.95);
    }

    .converter-card textarea:hover {
      backdrop-filter: none;
      filter: none;
    }

    .converter-card textarea:focus {
      backdrop-filter: none;
      filter: none;
      background: rgba(255, 255, 255, 1);
      box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
    }

    .converter-card textarea::placeholder {
      transition: opacity 0.3s ease;
    }

    .converter-card textarea:hover::placeholder {
      opacity: 0.5;
    }
  </style>
</head>

<body class="flex flex-col min-h-screen">
  <div class="container mx-auto px-4 py-8 flex-grow">
    <div class="max-w-6xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div class="flex justify-center items-center mb-4">
          <h1 class="text-4xl font-bold text-blue-600 mr-4">Shiroko ASCII Converter Pro</h1>
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
            <i class="fas fa-code text-blue-600 text-3xl"></i>
          </div>
        </div>
        <p class="text-gray-600 max-w-xl mx-auto">Convert, encrypt and share secret messages easily!</p>
      </div>

      <!-- Main Content Area -->
      <div class="grid md:grid-cols-3 gap-6">
        <!-- Converter Column -->
        <div class="md:col-span-2">
          <!-- Converter Card -->
          <div class="converter-card p-8 rounded-xl shadow-lg mb-6">
            <div class="grid md:grid-cols-3 gap-6 items-center">
              <!-- Input Section -->
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  <i class="fas fa-keyboard mr-2 text-blue-500"></i>Insert Your Text
                </label>
                <textarea id="inputText" rows="6"
                  class="w-full p-3 border-2 border-blue-200 rounded-lg code-font focus:outline-none focus:ring-2 focus:ring-blue-400 transition duration-300"
                  placeholder="Type text to convert..."><?php echo htmlspecialchars($lastInput); ?></textarea>
              </div>

              <!-- Conversion Controls -->
              <div class="flex flex-col items-center space-y-4">
                <span id="arrow"
                  class="arrow-button text-4xl text-gray-600 cursor-pointer"><?php echo $lastDirection === 'textToShirokocode' ? '&#8594;' : '&#8592;'; ?></span>
                <div class="flex space-x-2">
                  <button id="convert"
                    class="btn-glow py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                    Convert
                  </button>
                  <button id="generateRandom"
                    class="btn-glow py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-300 tooltip">
                    <i class="fas fa-random"></i>
                    <span class="tooltiptext">Generate Random</span>
                  </button>
                </div>
                <div class="flex space-x-2">
                  <button id="copyInput" class="text-gray-500 hover:text-blue-600 transition tooltip"
                    title="Salin Input">
                    <i class="fas fa-copy"></i>
                    <span class="tooltiptext">Copy Input</span>
                  </button>
                  <button id="clearInput" class="text-gray-500 hover:text-red-600 transition tooltip"
                    title="Bersihkan Input">
                    <i class="fas fa-trash-alt"></i>
                    <span class="tooltiptext">Clean Input</span>
                  </button>
                </div>
              </div>

              <!-- Output Section -->
              <div>
                <label class="block text-gray-700 font-semibold mb-2">
                  <i class="fas fa-file-alt mr-2 text-green-500"></i>Conversion Results
                </label>
                <textarea id="outputText" rows="6"
                  class="w-full p-3 border-2 border-green-200 rounded-lg code-font bg-white focus:outline-none focus:ring-2 focus:ring-green-400 transition duration-300"
                  placeholder="The conversion results will appear here..."><?php echo htmlspecialchars($lastOutput); ?></textarea>
                <div class="flex justify-end space-x-2 mt-2">
                  <button id="copyOutput" class="text-gray-500 hover:text-blue-600 transition tooltip"
                    title="Salin Hasil">
                    <i class="fas fa-copy"></i>
                    <span class="tooltiptext">Copy Result</span>
                  </button>
                  <button id="clearOutput" class="text-gray-500 hover:text-red-600 transition tooltip"
                    title="Bersihkan Hasil">
                    <i class="fas fa-trash-alt"></i>
                    <span class="tooltiptext">Clean Result</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Error Message Box -->
            <div id="errorBox" class="text-center mt-4 text-red-600 font-medium"></div>
          </div>

          <!-- Conversion History -->
          <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-semibold text-gray-800">
                <i class="fas fa-history mr-2 text-blue-500"></i>Conversion History
              </h2>
              <button id="clearHistory" class="text-red-500 hover:text-red-700 transition tooltip">
                <i class="fas fa-trash-alt"></i>
                <span class="tooltiptext">Delete History</span>
              </button>
            </div>
            <div id="conversionHistory" class="space-y-2 max-h-64 overflow-y-auto">
              <p class="text-gray-500 text-center">No conversion history yet...</p>
            </div>
          </div>
        </div>

        <!-- Sidebar Column -->
        <div>
          <!-- Features Section -->
          <div class="space-y-6">
            <div class="bg-white p-6 rounded-xl shadow-md">
              <h3 class="text-xl font-semibold mb-4 text-blue-700">
                <i class="fas fa-info-circle mr-2"></i>Context & How It Works
              </h3>
              <ul class="space-y-2 text-gray-600">
                <li>A meme that shiroko always says “Nn” in a chat or in a conversation, In the script section, when translating, there are parts that are not                            visible, this is because it uses special characters, namely unicode with the name zero width space (\u200B) and zero width non-joiner (\u200C),                       in converting, usually from text is converted to ahcii and changes in binary form, namely 0 and 1, after that it is converted to unicode which                        can hide the message.
                </li>
              </ul>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md">
              <h3 class="text-xl font-semibold mb-4 text-green-700">
                <i class="fas fa-shield-alt mr-2"></i>Security
              </h3>
              <p class="text-gray-600">
                Shiroko ASCII Code conversion using simple Unicode-based encryption techniques.
                Perfect for sharing secret messages with friends!
              </p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md">
              <h3 class="text-xl font-semibold mb-4 text-purple-700">
                <i class="fas fa-magic mr-2"></i>Additional Features
              </h3>
              <div class="space-y-3">
                <div class="flex items-center">
                  <i class="fas fa-random text-purple-500 mr-3"></i>
                  <span>Generate Random Code</span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-copy text-purple-500 mr-3"></i>
                  <span>Copy Instant Results</span>
                </div>
                <div class="flex items-center">
                  <i class="fas fa-history text-purple-500 mr-3"></i>
                  <span>Conversion History</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tutorial Modal -->
      <div id="tutorialModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl max-w-md w-full relative">
          <button id="closeTutorial" class="absolute top-4 right-4 text-gray-500 hover:text-red-500">
            <i class="fas fa-times text-2xl"></i>
          </button>
          <h2 class="text-2xl font-bold mb-4 text-blue-700">Usage Tutorial</h2>
          <div class="space-y-4 text-gray-700">
            <div class="flex items-start">
              <span class="mr-3 text-blue-500 font-bold">1.</span>
              <p>Select the conversion direction using the arrow keys (text to Shiroko Code or vice versa)</p>
            </div>
            <div class="flex items-start">
              <span class="mr-3 text-blue-500 font-bold">2.</span>
              <p>Enter the text you want to convert in the input field</p>
            </div>
            <div class="flex items-start">
              <span class="mr-3 text-blue-500 font-bold">3.</span>
              <p>Press the "Convert" button to generate the code</p>
            </div>
            <div class="flex items-start">
              <span class="mr-3 text-blue-500 font-bold">4.</span>
              <p>Use the copy button to quickly copy the results</p>
            </div>
            <div class="flex items-start">
              <span class="mr-3 text-blue-500 font-bold">5.</span>
              <p>And give it to your friend :3</p>
            </div>
          </div>
          <div class="mt-6 text-center">
            <button id="startTutorial" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
              Go!
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 text-white py-8 mt-8 shadow-lg">
    <div class="container mx-auto px-4 text-center">
      <div class="flex justify-center items-center space-x-6">
        <span class="text-lg md:text-xl font-semibold">
          &copy; <?php echo date("Y"); ?> Shiroko ASCII Converter, By <span class="font-bold">AndraZero121</span>. Inspired by 
          <a href="https://ernestoyoofi.github.io/shiroko-nn-code" target="_blank" class="text-yellow-300 hover:text-red-500 transition duration-300 font-semibold">
            @Ernestoyoofi
          </a>
        </span>
      </div>
      
      <div class="mt-6 flex justify-center items-center space-x-6 text-lg">
        <a href="https://github.com/AndraZero121" class="hover:text-blue-300 transition duration-300 transform hover:scale-110">
          <i class="fab fa-github"></i> GitHub
        </a>
        
        <a href="https://www.instagram.com/andrazero121" class="hover:text-pink-300 transition duration-300 transform hover:scale-110">
          <i class="fab fa-instagram"></i> Instagram
        </a>
        
        <button id="openTutorial" class="hover:text-green-300 transition duration-300 transform hover:scale-110">
          <i class="fas fa-question-circle"></i> Tutorial
        </button>
      </div>
      
      <div class="mt-6 text-sm text-gray-200">
        <p>Made with 💖 by AndraZero121</p>
      </div>
    </div>
  </footer>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const inputText = document.getElementById('inputText');
    const outputText = document.getElementById('outputText');
    const arrow = document.getElementById('arrow');
    const convertBtn = document.getElementById('convert');
    const generateRandomBtn = document.getElementById('generateRandom');
    const errorBox = document.getElementById('errorBox');
    const copyInputBtn = document.getElementById('copyInput');
    const clearInputBtn = document.getElementById('clearInput');
    const copyOutputBtn = document.getElementById('copyOutput');
    const clearOutputBtn = document.getElementById('clearOutput');
    const conversionHistory = document.getElementById('conversionHistory');
    const clearHistoryBtn = document.getElementById('clearHistory');

    // Tutorial Modal Elements
    const tutorialModal = document.getElementById('tutorialModal');
    const openTutorialBtn = document.getElementById('openTutorial');
    const closeTutorialBtn = document.getElementById('closeTutorial');
    const startTutorialBtn = document.getElementById('startTutorial');

    // Single direction initialization
    let direction = getCookie('lastDirection') || 'textToShirokocode';

    // Initial values
    inputText.value = getCookie('lastInput') || '';
    outputText.value = getCookie('lastOutput') || '';
    arrow.innerHTML = direction === 'textToShirokocode' ? '&#8594;' : '&#8592;';

    // Cookie helper function
    function getCookie(name) {
      const value = `; ${document.cookie}`;
      const parts = value.split(`; ${name}=`);
      if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    }

    function showMessage(msg, type = 'error') {
      errorBox.textContent = msg;
      errorBox.className = `text-center mt-4 ${type === 'error' ? 'text-red-600' : 'text-green-600'} font-medium`;
      setTimeout(() => {
        errorBox.textContent = '';
        errorBox.className = 'text-center mt-4 text-red-600 font-medium';
      }, 3000);
    }

    function copyToClipboard(element) {
      if (element.value.trim() === '') {
        showMessage('Tidak ada teks untuk disalin', 'error');
        return;
      }

      navigator.clipboard.writeText(element.value).then(() => {
        showMessage('Berhasil menyalin teks!', 'success');
      }).catch(() => {
        showMessage('Gagal menyalin teks', 'error');
      });
    }

    function updateHistory(history) {
      if (history.length === 0) {
        conversionHistory.innerHTML = '<p class="text-gray-500 text-center">Belum ada riwayat konversi</p>';
        return;
      }

      conversionHistory.innerHTML = history.map((entry, index) => `
        <div class="bg-gray-100 p-3 rounded-lg history-item cursor-pointer" 
             onclick="loadHistoryEntry(${JSON.stringify(entry).replace(/"/g, '&quot;')})">
            <div class="flex justify-between items-center">
                <span class="font-medium ${entry.type === 'textToShirokocode' ? 'text-blue-600' : 'text-green-600'}">
                    ${entry.type === 'textToShirokocode' ? 'Teks → Shiroko' : 'Shiroko → Teks'}
                </span>
                <span class="text-xs text-gray-500">${entry.timestamp}</span>
            </div>
            <div class="text-sm text-gray-700 mt-1 truncate">
                Input: ${htmlEscape(entry.input)}
            </div>
            <div class="text-sm text-gray-700 mt-1 truncate">
                Output: ${htmlEscape(entry.output)}
            </div>
        </div>
    `).join('');
    }

    // Add these helper functions
    function htmlEscape(str) {
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function loadHistoryEntry(entry) {
      direction = entry.type;
      arrow.innerHTML = direction === 'textToShirokocode' ? '&#8594;' : '&#8592;';
      inputText.value = entry.input;
      outputText.value = entry.output;
    }

    // Conversion Direction Toggle
    arrow.addEventListener('click', () => {
      direction = direction === 'textToShirokocode' ? 'shirokocodeToText' : 'textToShirokocode';
      arrow.innerHTML = direction === 'textToShirokocode' ? '&#8594;' : '&#8592;';
    });

    // Convert Button
    convertBtn.addEventListener('click', () => {
      const inputValue = direction === 'textToShirokocode' ? inputText.value : outputText.value;
      if (!inputValue.trim()) {
        showMessage('Masukkan teks untuk dikonversi');
        return;
      }

      const formData = new URLSearchParams();
      formData.append('action', direction);
      formData.append(direction === 'textToShirokocode' ? 'text' : 'shirokocode', inputValue);

      fetch('index', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            if (direction === 'textToShirokocode') {
              outputText.value = data.result;
            } else {
              inputText.value = data.result;
            }
            showMessage('Konversi berhasil!', 'success');
            updateHistory(data.history);
          } else {
            throw new Error(data.message || 'Konversi gagal');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showMessage('Terjadi kesalahan pada server');
        });
    });

    // Generate Random Button
    generateRandomBtn.addEventListener('click', () => {
      fetch('index', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'generateRandom'
          })
        })
        .then(res => {
          if (!res.ok) throw new Error('Network response was not ok');
          return res.json();
        })
        .then(data => {
          if (data.status === 'success') {
            inputText.value = data.result;
            showMessage('Kode acak berhasil dibuat!', 'success');
          } else {
            throw new Error(data.message || 'Gagal membuat kode acak');
          }
        })
        .catch(handleServerError);
    });

    // Copy and Clear Buttons
    copyInputBtn.addEventListener('click', () => copyToClipboard(inputText));
    clearInputBtn.addEventListener('click', () => inputText.value = '');
    copyOutputBtn.addEventListener('click', () => copyToClipboard(outputText));
    clearOutputBtn.addEventListener('click', () => outputText.value = '');

    // Clear History Button
    clearHistoryBtn.addEventListener('click', () => {
      fetch('index', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'clearHistory'
          })
        })
        .then(res => {
          if (!res.ok) throw new Error('Network response was not ok');
          return res.json();
        })
        .then(data => {
          updateHistory(data.history);
          showMessage('Riwayat berhasil dihapus!', 'success');
        })
        .catch(handleServerError);
    });

    // Tutorial Modal Interactions
    openTutorialBtn.addEventListener('click', () => {
      tutorialModal.classList.remove('hidden');
    });

    closeTutorialBtn.addEventListener('click', () => {
      tutorialModal.classList.add('hidden');
    });

    startTutorialBtn.addEventListener('click', () => {
      tutorialModal.classList.add('hidden');
    });

    // Optional: Close modal when clicking outside
    tutorialModal.addEventListener('click', (e) => {
      if (e.target === tutorialModal) {
        tutorialModal.classList.add('hidden');
      }
    });

    // Load initial history
    updateHistory(<?php echo json_encode($history->getHistory()); ?>);
  });

  function handleServerError(error) {
    console.error('Server Error:', error);
    showMessage('Terjadi kesalahan pada server. Silakan coba lagi.');
  }
  </script>
</body>

</html>