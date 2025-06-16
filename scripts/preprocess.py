import os
import re
import glob
import string
import pandas as pd
import nltk
import pytz
from nltk.corpus import stopwords
from nltk.tokenize import word_tokenize
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

def ensure_nltk_data(nltk_data_dir='./nltk_data'):
    nltk.data.path.append(nltk_data_dir)
    try:
        nltk.data.find('tokenizers/punkt')
    except LookupError:
        nltk.download('punkt', download_dir=nltk_data_dir)
    try:
        nltk.data.find('corpora/stopwords')
    except LookupError:
        nltk.download('stopwords', download_dir=nltk_data_dir)

def extract_version_from_filename(fname):
    nums = re.findall(r'\d+', os.path.basename(fname))
    return int(nums[-1]) if nums else 0

def clean_text_basic(text):
    t = str(text).lower()
    t = re.sub(r"http\S+|www\S+", " ", t)
    t = re.sub(r"@\w+|#\w+", " ", t)
    t = re.sub(r"\d+", " ", t)
    t = t.translate(str.maketrans("", "", string.punctuation))
    t = re.sub(r"\s+", " ", t).strip()
    return t

def tokenize_nltk(text):
    return word_tokenize(text)

def load_kbbi_and_abbr(data_dir):
    kbbi_path = os.path.join(data_dir, 'kbbi.csv')
    abbr_path = os.path.join(data_dir, 'singkatan-lib.csv')
    if not os.path.exists(kbbi_path) or not os.path.exists(abbr_path):
        raise FileNotFoundError(f"Pastikan {kbbi_path} dan {abbr_path} ada.")
    kbbi_df = pd.read_csv(kbbi_path)
    if 'kata' not in kbbi_df.columns:
        raise ValueError(f"Kolom 'kata' tidak ditemukan di {kbbi_path}")
    kbb_words = set(kbbi_df['kata'].astype(str).str.lower().str.strip())
    abbr_lib = pd.read_csv(abbr_path, header=None, names=['abbr','normal'])
    abbr_lib['abbr'] = abbr_lib['abbr'].astype(str).str.lower().str.strip()
    abbr_lib['normal'] = abbr_lib['normal'].astype(str).str.lower().str.strip()
    abbr_map = dict(zip(abbr_lib['abbr'], abbr_lib['normal']))
    return kbb_words, abbr_map

def load_sentiment_lexicons_txt(data_dir):
    pos_path = os.path.join(data_dir, 'positive.txt')
    neg_path = os.path.join(data_dir, 'negative.txt')
    if not os.path.exists(pos_path) or not os.path.exists(neg_path):
        raise FileNotFoundError(f"Pastikan lexicon sentimen ada: {pos_path} & {neg_path}")
    # Baca tiap baris, buang empty dan komentar (misal baris mulai '#')
    def load_txt(path):
        words = set()
        with open(path, encoding='utf-8') as f:
            for line in f:
                w = line.strip().lower()
                if not w or w.startswith('#'):
                    continue
                words.add(w)
        return words
    pos_words = load_txt(pos_path)
    neg_words = load_txt(neg_path)
    return pos_words, neg_words

def reconstruct_sentence(tokens):
    text = ' '.join(tokens)
    text = re.sub(r'\s+([,.!?])', r'\1', text)
    if text and text[-1] not in '.!?':
        text += '.'
    return text

def main():
    ensure_nltk_data()

    BASE_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    UPLOAD_DIR = os.path.join(BASE_DIR, 'storage', 'app', 'uploads')
    OUTPUT_DIR = os.path.join(BASE_DIR, 'storage', 'app', 'processed')
    DATA_DIR = os.path.join(BASE_DIR, 'storage', 'app', 'data')

    os.makedirs(OUTPUT_DIR, exist_ok=True)

    # Debug direktori
    print("DEBUG: DATA_DIR =", DATA_DIR, "Exists?", os.path.exists(DATA_DIR))
    if os.path.exists(DATA_DIR):
        print("DEBUG: files in data dir:", os.listdir(DATA_DIR))
    print("DEBUG: UPLOAD_DIR =", UPLOAD_DIR, "Exists?", os.path.exists(UPLOAD_DIR))
    if os.path.exists(UPLOAD_DIR):
        print("DEBUG: files in uploads:", os.listdir(UPLOAD_DIR))
    print("DEBUG: OUTPUT_DIR =", OUTPUT_DIR, "Exists?", os.path.exists(OUTPUT_DIR))

    # Load kamus KBBI & singkatan
    try:
        kbb_words, abbr_map = load_kbbi_and_abbr(DATA_DIR)
        print(f"Loaded {len(kbb_words)} KBBI words and {len(abbr_map)} abbreviations")
    except Exception as e:
        print("Gagal load kamus:", e)
        return

    # Load sentiment lexicon dari txt
    try:
        pos_words, neg_words = load_sentiment_lexicons_txt(DATA_DIR)
        print(f"Loaded {len(pos_words)} positive words and {len(neg_words)} negative words")
    except Exception as e:
        print("Warning: gagal load sentiment lexicon:", e)
        pos_words, neg_words = set(), set()

    # Cari data CSV di uploads (abaikan kamus/lexicon jika ikut di uploads)
    csv_files = glob.glob(os.path.join(UPLOAD_DIR, '*.csv'))
    if not csv_files:
        print("Tidak ada file CSV ditemukan di folder uploads.")
        return
    data_files = []
    for fpath in csv_files:
        name = os.path.basename(fpath).lower()
        if name in ['kbbi.csv','singkatan-lib.csv','positive.txt','negative.txt']:
            continue
        data_files.append(fpath)
    if not data_files:
        print("Tidak ada file data untuk diproses di uploads.")
        return
    print(f"Ditemukan {len(data_files)} file data:")
    for f in data_files:
        print(" -", os.path.basename(f))

    # Baca & merge semua data CSV
    df_list = []
    for fpath in data_files:
        try:
            tmp = pd.read_csv(fpath)
        except Exception as e:
            print(f"Gagal membaca {fpath}: {e}, dilewati.")
            continue
        if 'full_text' not in tmp.columns:
            if 'Tweet' in tmp.columns:
                tmp = tmp.rename(columns={'Tweet':'full_text'})
            else:
                print(f"File {os.path.basename(fpath)} tak punya kolom 'full_text' atau 'Tweet'; dilewati.")
                continue
        tmp['version'] = extract_version_from_filename(fpath)
        df_list.append(tmp)
    if not df_list:
        print("Tidak ada file data valid.")
        return

    df = pd.concat(df_list, ignore_index=True)
    print("=== Setelah Load & Merge Data ===")
    print(df[['full_text']].head(), f"\nTotal rows: {len(df)}\n")

    # Konversi waktu jika ada
    if 'created_at' in df.columns:
        try:
            df['created_at'] = pd.to_datetime(
                df['created_at'], format='%a %b %d %H:%M:%S %z %Y',
                errors='coerce', utc=True
            )
            df = df.dropna(subset=['created_at'])
            jakarta = pytz.timezone('Asia/Jakarta')
            df['created_at'] = df['created_at'].dt.tz_convert(jakarta)
            df['tweet_date'] = df['created_at'].dt.date
            df['tweet_hour'] = df['created_at'].dt.hour
            print("=== Setelah Konversi & Ekstrak Waktu ===")
            print(df[['created_at','tweet_date','tweet_hour']].head(), "\n")
        except Exception as e:
            print("Gagal convert 'created_at':", e)

    # Drop kolom tak terpakai
    drop_cols = [
        'conversation_id_str','id_str','tweet_url',
        'image_url','in_reply_to_screen_name',
        'user_id_str','username','location'
    ]
    cols_to_drop = [c for c in drop_cols if c in df.columns]
    if cols_to_drop:
        df = df.drop(columns=cols_to_drop)
    print("=== Setelah Drop Kolom Tak Terpakai ===")
    print(df.head(), "\n")

    # Drop missing & duplicates
    df = df.dropna(subset=['full_text'])
    df = df.drop_duplicates(subset=['full_text']).reset_index(drop=True)
    print("=== Setelah Drop Missing & Duplicates ===")
    print(df[['full_text']].head(), "\n")

    # Inisialisasi stemmer & stopwords
    stemmer = StemmerFactory().create_stemmer()
    stop_words = set(stopwords.words('indonesian'))
    manual_keep_words = {
        'ios','iphone','update','upgrade','downgrade',
        'baterai','bug','memori','install','uninstall',
        'charge','layar','notifikasi'
    }
    replacement_dict = {
        'hp':'handphone','hape':'handphone',
        'app':'aplikasi','apk':'aplikasi',
        'os':'sistem operasi','bt':'bluetooth','wifi':'wi-fi',
        'cam':'kamera','notif':'notifikasi',
        'batt':'baterai','batre':'baterai','bat':'baterai',
        'charge':'mengisi daya','charging':'mengisi daya',
        'layar':'layar','lcd':'layar',
        'memori':'memori','ram':'memori',
        'bug':'kesalahan','bugs':'kesalahan',
        'update':'pembaruan','updated':'diperbarui',
        'downgrade':'penurunan versi',
        'install':'pasang','instal':'pasang',
        'uninstall':'copot','hapus':'copot'
    }

    # Preprocessing dasar
    def preprocess_basic(text):
        t = clean_text_basic(text)
        toks = tokenize_nltk(t)
        toks = [w for w in toks if w not in stop_words]
        joined = " ".join(toks)
        return stemmer.stem(joined)

    df['clean_text'] = df['full_text'].apply(preprocess_basic)
    print("=== Sample clean_text dasar ===")
    print(df['clean_text'].head(), "\n")

    # Tokenisasi & lexicon filtering
    df['tokens'] = df['clean_text'].astype(str).str.split()
    df['mapped'] = df['tokens'].apply(lambda toks: [replacement_dict.get(t, t) for t in toks])
    df['filt_abbr_len'] = df['mapped'].apply(lambda toks: [t for t in toks if len(t)>=3])
    df['filt_lexicon'] = df['filt_abbr_len'].apply(
        lambda toks: [t for t in toks if t in kbb_words or t in manual_keep_words]
    )
    df['stemmed'] = df['filt_lexicon'].apply(lambda toks: [stemmer.stem(t) for t in toks])
    df['stemmed_replaced'] = df['stemmed'].apply(lambda toks: [replacement_dict.get(t, t) for t in toks])
    print("=== Sample token pipeline ===")
    print(df[['tokens','mapped','filt_abbr_len','filt_lexicon','stemmed_replaced']].head(), "\n")

    # Auto-label: 0=Netral, 1=Positif, 2=Negatif
    def auto_label(tokens):
        pos_count = sum(1 for t in tokens if t in pos_words)
        neg_count = sum(1 for t in tokens if t in neg_words)
        if pos_count > neg_count:
            return 1
        elif neg_count > pos_count:
            return 2
        else:
            return 0

    df['label'] = df['stemmed_replaced'].apply(auto_label)
    print("=== Distribusi label otomatis ===")
    print(df['label'].value_counts(dropna=False), "\n")

    # Reconstruct kalimat & drop pendek
    df['filtered_tokens'] = df['stemmed_replaced']
    def remove_consecutive_duplicates(tokens):
        dedup=[]; prev=None
        for t in tokens:
            if t!=prev:
                dedup.append(t)
            prev=t
        return dedup

    df['dedup_tokens'] = df['filtered_tokens'].apply(remove_consecutive_duplicates)
    df['reconstructed'] = df['dedup_tokens'].apply(reconstruct_sentence)
    df['recon_word_count'] = df['reconstructed'].str.split().str.len()
    before = len(df)
    df = df[df['recon_word_count']>=3].reset_index(drop=True)
    after = len(df)
    print(f"Dropped {before-after} baris <3 kata, sisa {after}")
    print("=== Sample reconstruction ===")
    print(df[['filtered_tokens','dedup_tokens','reconstructed']].head(), "\n")

    # Hitung word_count
    df['word_count'] = df['clean_text'].str.split().str.len()

    # Simpan hasil
    output_file = os.path.join(OUTPUT_DIR, 'data.csv')
    cols_out = []
    for c in ['version','tweet_date','created_at','full_text','clean_text','tokens',
              'filtered_tokens','dedup_tokens','reconstructed','word_count','label']:
        if c in df.columns:
            cols_out.append(c)
    df.to_csv(output_file, columns=cols_out, index=False)
    print(f"✔ Data preparation selesai, file disimpan di: {output_file}")

if __name__=='__main__':
    main()
