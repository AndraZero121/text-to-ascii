# Shiroko ASCII Converter Pro 🎮

<div align="center">
  <img src="https://media1.tenor.com/m/ropc7R3ldMkAAAAC/shiroko-iwak.gif" width="200" height="200">
  <p><em>Convert your messages into Shiroko's secret code!</em></p>
</div>

## 📝 Overview

A web-based text encoder/decoder that converts text to and from Shiroko ASCII code using special Unicode characters. This fun project lets you create secret messages using invisible Unicode characters, themed around Blue Archive's Shiroko character. This is using PHP for Programming Language.

## 🌟 Features

- ✨ Text to Shiroko ASCII code conversion
- 🔄 Shiroko ASCII code to text decoding
- 🎲 Random code generation
- 📜 Conversion history tracking
- 📋 Quick copy/paste functionality
- 📱 Mobile-responsive design
- 🔒 Secure input sanitization
- 💾 Session and cookie-based storage

## 🔧 Technical Details

### How It Works

The converter uses two special Unicode characters to create "invisible" messages:

- Zero Width Space (`\u200B`) - Represents binary 0
- Zero Width Non-Joiner (`\u200C`) - Represents binary 1

Each character is prefixed with "Nn" (Shiroko's characteristic expression) and the message ends with "Sensei...".

### Built With

- PHP 8.0+
- JavaScript (ES6+)
- TailwindCSS
- Font Awesome Icons
- Google Fonts (Poppins, Source Code Pro)

## 🚀 Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/shiroko-ascii-converter.git
```

2. Put to your htdocs directory:

```bash
C:\Apache24\htdocs / C:\xampp\htdocs
```

3. Access through your browser:

```
http://localhost/shiroko-ascii-converter
```

## ⚙️ Requirements

- PHP 8.0 or higher
- Apache web server
- Modern web browser
- JavaScript enabled
- Cookie support

## 💡 Usage

1. Enter text in the input field
2. Click "Convert" to generate Shiroko code
3. Use the arrow button to switch conversion direction
4. Copy results with the copy button
5. View conversion history below

## 🔐 Security Features

- Input sanitization
- XSS protection
- CSRF prevention
- Secure cookie handling
- Error handling and validation

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📜 License

Distributed under the MIT License. See `LICENSE` for more information.

## 🙏 Acknowledgments

- Inspired by [@Ernestoyoofi's Shiroko NN Code](https://ernestoyoofi.github.io/shiroko-nn-code)
- Blue Archive community
- TailwindCSS team
- Font Awesome team

## 👤 Author

**AndraZero121**

- GitHub: [@AndraZero121](https://github.com/AndraZero121)
- Instagram: [@andrazero121](https://www.instagram.com/andrazero121)

---

<div align="center">
  Made with 💖 by AndraZero121
  <br>
  ©2025 Shiroko ASCII Converter. All rights reserved.
</div>
