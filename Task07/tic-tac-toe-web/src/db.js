const DB_NAME = 'tic_tac_toe_db';
const DB_VERSION = 1;

export const DB = {
  async open() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains('games')) {
          const store = db.createObjectStore('games', { keyPath: 'id', autoIncrement: true });
          store.createIndex('date', 'date', { unique: false });
        }
      };

      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  },

  async addGame(gameObj) {
    const db = await this.open();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('games', 'readwrite');
      const store = tx.objectStore('games');
      const req = store.add(gameObj);

      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  },

  async getGames() {
    const db = await this.open();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('games', 'readonly');
      const store = tx.objectStore('games');
      const req = store.getAll();

      req.onsuccess = () => {
        const sorted = req.result.sort((a, b) => new Date(b.date) - new Date(a.date));
        resolve(sorted);
      };
      req.onerror = () => reject(req.error);
    });
  },

  async getGameById(id) {
    const db = await this.open();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('games', 'readonly');
      const store = tx.objectStore('games');
      const req = store.get(id);

      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }
};
