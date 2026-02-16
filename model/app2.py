from flask import Flask, jsonify, request
import pandas as pd
from sqlalchemy import create_engine, text
from sentence_transformers import SentenceTransformer
from transformers import pipeline
from sklearn.metrics.pairwise import cosine_similarity
import logging

app = Flask(__name__)

# ==========================================
# KONFIGURASI DATABASE 
# ==========================================
DB_IP   = "127.0.0.1"
DB_PORT = "3308"      
DB_USER = "root"
DB_PASS = ""          
DB_NAME = "sistemcdc" 

# Membuat string koneksi
connection_url = f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_IP}:{DB_PORT}/{DB_NAME}"
db_engine = create_engine(connection_url)

# --- CEK KONEKSI SAAT STARTUP ---
try:
    with db_engine.connect() as conn:
        print(f"✅ BERHASIL TERHUBUNG KE DATABASE: {DB_NAME}")
except Exception as e:
    print(f"GAGAL KONEKSI DATABASE. Pastikan XAMPP Hidup & Port Benar.")
    print(f"Pesan Error: {e}")

# ==========================================
# LOAD MODEL
# ==========================================
print("Sedang memuat model... Mohon tunggu sebentar.")
# Model Embedding (E5)
embed_model = SentenceTransformer('intfloat/multilingual-e5-small')
# Model Sentimen (IndoBERT)
sentiment_pipeline = pipeline("sentiment-analysis", model="w11wo/indonesian-roberta-base-sentiment-classifier")
print("SISTEM SIAP DIGUNAKAN!")

# ==========================================
# LOGIKA UTAMA (API)
# ==========================================
@app.route('/api/rekomendasi-final/<int:id_asesmen>', methods=['GET'])
def get_final_recommendation(id_asesmen):
    try:
        # Pertama: Mengambil permasalahan dari db
        query_masalah = text("SELECT permasalahan FROM asesmen WHERE id_asesmen = :id")
        
        with db_engine.connect() as conn:
            df_masalah = pd.read_sql(query_masalah, conn, params={"id": id_asesmen})
        
        if df_masalah.empty:
            return jsonify({"status": "error", "message": "ID Asesmen tidak ditemukan"}), 404
            
        user_problem = df_masalah.iloc[0]['permasalahan']

        # Kedua: mengambil data hasil dss
        query_jobs = text("""
            SELECT 
                hr.id_rekomendasi,
                hr.id_pekerjaan,
                hr.hasil_skor AS dss_score,
                p.nama_pekerjaan,
                p.ket_pekerjaan
            FROM hasil_rekomendasi hr
            JOIN profil_pekerjaan p ON hr.id_pekerjaan = p.id_pekerjaan
            WHERE hr.id_asesmen = :id
        """)
        
        with db_engine.connect() as conn:
            df_jobs = pd.read_sql(query_jobs, conn, params={"id": id_asesmen})
        
        if df_jobs.empty:
            return jsonify({"status": "error", "message": "Belum ada data DSS untuk asesmen ini"}), 404

        # Ketiga: Melakukan analisis sentimen pada permasalahan mahasiswa
        try:
            # Memaksimalkan input ke 512 token
            sentiment_result = sentiment_pipeline(user_problem[:512])[0]
            label = sentiment_result['label']
        except:
            label = 'neutral'
        
        # Logika Bonus/Penalty
        if label == 'negative':
            multiplier = -1 
        elif label == 'positive':
            multiplier = 1  
        else:
            multiplier = 0
        
        sensitivity = 1.5 

        # Keempat: Menghitung Kedekatan dan Skor Akhir
        # Encode Permasalahan User
        user_vec = embed_model.encode([f"query: {user_problem}"])
        
        results = []
        
        for _, job in df_jobs.iterrows():
            # Encode Deskripsi Pekerjaan
            text_job = f"{job['nama_pekerjaan']}. {job['ket_pekerjaan']}"
            job_vec = embed_model.encode([f"passage: {text_job}"])
            
            # Hitung Kemiripan (Cosine Similarity)
            similarity = cosine_similarity(user_vec, job_vec)[0][0]
            
            # Rumus Penggabungan
            adjustment = similarity * sensitivity * multiplier
            final_score = job['dss_score'] + adjustment
            
            results.append({
                "id_rekomendasi": int(job['id_rekomendasi']),
                "nama_pekerjaan": job['nama_pekerjaan'],
                "skor_dss_awal": round(job['dss_score'], 4),
                "analisis_ai": {
                    "sentimen_user": label,
                    "kecocokan_job": round(float(similarity), 4),
                    "adjustment": round(float(adjustment), 4)
                },
                "skor_akhir": round(float(final_score), 4)
            })
            
        # Kelima: Ranking Ulang
        # Urut dari skor tertinggi ke terendah
        sorted_results = sorted(results, key=lambda x: x['skor_akhir'], reverse=True)
        
        # Tambahkan ranking 1, 2, 3
        for i, item in enumerate(sorted_results):
            item['rank_baru'] = i + 1

# Update ke database
        print("💾 Sedang menyimpan hasil...")
        
        with db_engine.connect() as conn:
            trans = conn.begin()
            try:
                # Batasi hanya 3 teratas 
                top_3_results = sorted_results[:3] 
                
                for item in top_3_results:
                    rank_str = str(item['rank_baru']) 
                    
                    query_update = text("""
                        UPDATE hasil_rekomendasi 
                        SET urutan_baru = :rank_enum, 
                            hasil_skor_baru = :skor_baru  
                        WHERE id_rekomendasi = :id_rek
                    """)
                    
                    conn.execute(query_update, {
                        "rank_enum": rank_str,               
                        "skor_baru": item['skor_akhir'],
                        "id_rek": item['id_rekomendasi']
                    })
                
                trans.commit()
                print("✅ Database berhasil diupdate! Ranking 1-3 tersimpan.")
                
            except Exception as e:
                trans.rollback()
                print(f"❌ Gagal update database: {e}")
                print("Tips: Pastikan Python tidak mengirim ranking 4 ke kolom ENUM('1','2','3')")

        return jsonify({
            "status": "success",
            "pesan": "Berhasil menghitung dan update database",
            "data": sorted_results
        })

    except Exception as e:
        print(f"Error System: {e}")
        return jsonify({"status": "error", "message": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True, port=5001)