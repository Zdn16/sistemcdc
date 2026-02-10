from flask import Flask, request, jsonify, render_template
import joblib
from sentence_transformers import SentenceTransformer
import sqlite3
import os

app = Flask(__name__)

# --- SETUP DATABASE ---
# otomatis db jika belum ada
def init_db():
    conn = sqlite3.connect('database_masalah.db')
    c = conn.cursor()
    # tabel sederhana template
    c.execute('''CREATE TABLE IF NOT EXISTS laporan 
                 (id INTEGER PRIMARY KEY AUTOINCREMENT, 
                  isi_masalah TEXT, 
                  kategori_prediksi TEXT)''')
    conn.commit()
    conn.close()

# Jalankan fungsi pembuatan database saat aplikasi mulai
init_db()

# --- LOAD MODEL ---
print("Sedang memuat model... Mohon tunggu sebentar.")

# Load Model Logistic Regression 
model_logreg = joblib.load('model_logreg.pkl')

# Load Model Sentence Transformer (Untuk mengubah teks jadi angka)
emb_model = SentenceTransformer("intfloat/multilingual-e5-small")

print("Sistem siap digunakan!")

# --- ROUTE / HALAMAN ---

# Halaman utama
@app.route('/')
def home():
    # Ini akan memanggil file index.html dari folder templates
    return render_template('index.html')

# API untuk menerima data masalah dari Front-End / Postman
@app.route('/predict', methods=['POST'])
def predict():
    try:
        # Ambil data JSON yang dikirim
        data = request.json
        input_text = data['masalah'] # Pastikan pengirim pakai kunci 'masalah'
        
        # PROSES EMBEDDING (Ubah teks jadi angka)
        # normalize_embeddings=True
        input_vector = emb_model.encode([input_text], normalize_embeddings=True)
        
        # PREDIKSI (Tebak kategori)
        hasil_prediksi = model_logreg.predict(input_vector)[0]
        
        # SIMPAN KE DATABASE
        conn = sqlite3.connect('database_masalah.db')
        c = conn.cursor()
        c.execute("INSERT INTO laporan (isi_masalah, kategori_prediksi) VALUES (?, ?)", 
                  (input_text, hasil_prediksi))
        conn.commit()
        conn.close()
        
        # Kirim jawaban kembali ke pengirim
        return jsonify({
            'status': 'sukses',
            'input_masalah': input_text,
            'kategori': hasil_prediksi,
            'pesan': 'Data berhasil disimpan ke database'
        })

    except Exception as e:
        return jsonify({'status': 'error', 'pesan': str(e)})

# --- JALANKAN APLIKASI ---
if __name__ == "__main__":
    app.run(debug=True, port=5000)