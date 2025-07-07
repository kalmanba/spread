class WordDisplay {
    constructor() {
        // Sample array - you can replace this with your own array
        this.words = wordsArray;
        
        this.currentIndex = 0;
        this.isPlaying = false;
        this.intervalId = null;
        this.speed = 120; // words per minute
        this.isStopped = false;
        
        this.initializeElements();
        this.loadState();
        this.setupEventListeners();
        this.updateStatus();
    }
    
    initializeElements() {
        this.displayElement = document.getElementById('display');
        this.speedInput = document.getElementById('speed');
        this.playBtn = document.getElementById('playBtn');
        this.pauseBtn = document.getElementById('pauseBtn');
        this.resetBtn = document.getElementById('resetBtn');
        this.stopBtn = document.getElementById('stopBtn');
        this.statusElement = document.getElementById('status');
    }
    
    setupEventListeners() {
        this.playBtn.addEventListener('click', () => this.play());
        this.pauseBtn.addEventListener('click', () => this.pause());
        this.resetBtn.addEventListener('click', () => this.reset());
        //this.stopBtn.addEventListener('click', () => this.stop());
        this.speedInput.addEventListener('change', () => this.updateSpeed());
    }
    
    play() {
        if (this.isPlaying || this.isStopped) return;
        
        this.isPlaying = true;
        this.speed = parseInt(this.speedInput.value) || 120;
        
        // Calculate interval in milliseconds (60000ms per minute / words per minute)
        const intervalMs = 60000 / this.speed;
        
        this.intervalId = setInterval(() => {
            this.displayNextWord();
        }, intervalMs);
        
        this.updateStatus();
    }
    
    pause() {
        if (!this.isPlaying || this.isStopped) return;
        
        this.isPlaying = false;
        clearInterval(this.intervalId);
        this.saveState();
        this.updateStatus();
    }
    
    stop() {
        this.isPlaying = false;
        this.isStopped = true;
        clearInterval(this.intervalId);
        this.displayElement.textContent = "Stopped";
        this.clearState();
        this.updateStatus();
    }
    
    reset() {
        this.isPlaying = false;
        this.isStopped = false;
        clearInterval(this.intervalId);
        this.currentIndex = 0;
        this.displayElement.textContent = "Indításra kész...";
        this.clearState();
        this.updateStatus();
    }
    
    displayNextWord() {
        if (this.currentIndex >= this.words.length) {
            this.pause();
            this.displayElement.textContent = "Kész!!";
            return;
        }
        
        this.displayElement.textContent = this.words[this.currentIndex];
        this.currentIndex++;
        this.updateStatus();
    }
    
    updateSpeed() {
        this.speed = parseInt(this.speedInput.value) || 120;
        
        // If currently playing, restart with new speed
        if (this.isPlaying) {
            clearInterval(this.intervalId);
            const intervalMs = 60000 / this.speed;
            this.intervalId = setInterval(() => {
                this.displayNextWord();
            }, intervalMs);
        }
        
        this.updateStatus();
    }
    
    updateStatus() {
        const status = this.isStopped ? "Leállítva" :
                      this.isPlaying ? "Lejátszás" : 
                      this.currentIndex === 0 ? "Kész" : "Megállítva";
        this.statusElement.textContent = 
            `Pozíció: ${this.currentIndex}/${this.words.length} | Sebesség: ${this.speed} WPM | Státusz: ${status}`;
    }
    
    saveState() {
        const state = {
            currentIndex: this.currentIndex,
            speed: this.speed
        };
        
        // Set cookie with 30 day expiration
        const expires = new Date();
        expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
        document.cookie = `wordDisplayState=${JSON.stringify(state)};expires=${expires.toUTCString()};path=/`;
    }
    
    loadState() {
        const cookies = document.cookie.split(';');
        const stateCookie = cookies.find(cookie => 
            cookie.trim().startsWith('wordDisplayState=')
        );
        
        if (stateCookie) {
            try {
                const stateValue = stateCookie.split('=')[1];
                const state = JSON.parse(decodeURIComponent(stateValue));
                
                this.currentIndex = state.currentIndex || 0;
                this.speed = state.speed || 120;
                this.speedInput.value = this.speed;
                
                // Display current word if we're in the middle
                if (this.currentIndex > 0 && this.currentIndex < this.words.length) {
                    this.displayElement.textContent = this.words[this.currentIndex - 1];
                }
                
            } catch (e) {
                console.error('Error loading saved state:', e);
            }
        }
    }
    
    clearState() {
        document.cookie = 'wordDisplayState=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
    }
}

// Global variable to store the WordDisplay instance
let wordDisplayInstance = null;

// Initialize the word display when the page loads
//document.addEventListener('DOMContentLoaded', () => {
//    wordDisplayInstance = new WordDisplay();
//    
//    // Make it globally accessible via window object as well
//    window.wordDisplay = wordDisplayInstance;
//});
//
// Example functions showing how to call methods from other JS functions
//function stopFromExternal() {
//    if (wordDisplayInstance) {
//        wordDisplayInstance.stop();
//        console.log('WordDisplay stopped from external function');
//    }
//}
//
//function playFromExternal() {
//    if (wordDisplayInstance) {
//        wordDisplayInstance.play();
//        console.log('WordDisplay started from external function');
//    }
//}
//
//function resetFromExternal() {
//    if (wordDisplayInstance) {
//        wordDisplayInstance.reset();
//        console.log('WordDisplay reset from external function');
//    }
//}
//
//function setSpeedFromExternal(newSpeed) {
//    if (wordDisplayInstance) {
//        wordDisplayInstance.speedInput.value = newSpeed;
//        wordDisplayInstance.updateSpeed();
//        console.log(`Speed set to ${newSpeed} WPM from external function`);
//    }
//}
//
////// Initialize the word display when the page loads
////document.addEventListener('DOMContentLoaded', () => {
////    new WordDisplay();
//});

const fileInput = document.getElementById('fileInput');
const fileLabel = document.getElementById('fileLabel');
const filenameDisplay = document.getElementById('filename');
const form = document.getElementById('fileForm');

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const maxLength = 18;
        const truncated = file.name.length > maxLength
            ? file.name.slice(0, maxLength) + '...'
            : file.name;

        filenameDisplay.textContent = truncated;

        // Hide label
        fileLabel.style.display = 'none';

        // Create and add a submit button
        const existingButton = document.getElementById('submitBtn');
        if (!existingButton) {
            const submitBtn = document.createElement('button');
            submitBtn.type = 'submit';
            submitBtn.id = 'submitBtn';
            submitBtn.textContent = 'Feltöltés';
            submitBtn.style.cssText = `
                background: #002b5b;
                color: white;
                border-radius: 4px;
                width: 170px;
                cursor: pointer;
                font-size: medium;
                font-weight: 300;
        `;
            const span = document.getElementById('filename');
            form.insertBefore(submitBtn, span);
        }
    }
});
