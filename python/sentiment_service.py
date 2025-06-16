#!/usr/bin/env python3
import argparse, json, os, sys
import pandas as pd
import numpy as np
import math
import joblib
import matplotlib.pyplot as plt
from sklearn.feature_extraction.text import TfidfVectorizer, CountVectorizer
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import MultinomialNB
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from wordcloud import WordCloud

def load_data(path):
    if not os.path.exists(path):
        print(json.dumps({"status":"error","message":f"File not found: {path}"}))
        sys.exit(1)
    df = pd.read_csv(path)
    return df

def sub_train(args):
    df = load_data(args.data)
    mapping = {0: 'Netral', 1: 'Positif', 2: 'Negatif'}
    # Filter data valid
    df = df[df['label'].isin(mapping.keys()) & df['clean_text'].fillna("").str.strip().astype(bool)].reset_index(drop=True)
    docs = df['clean_text'].tolist()
    y = df['label'].values

    # Grid search test_size
    test_sizes = [0.2, 0.25, 0.3]
    alpha = 1.0
    best_acc = 0
    best_ts = None
    for ts in test_sizes:
        Xtr, Xte, ytr, yte = train_test_split(docs, y, test_size=ts, stratify=y, random_state=42)
        vect = TfidfVectorizer(lowercase=True, token_pattern=r"(?u)\b\w+\b", min_df=1, ngram_range=(1,2))
        X_tr = vect.fit_transform(Xtr)
        X_te = vect.transform(Xte)
        clf = MultinomialNB(alpha=alpha, fit_prior=False)
        clf.fit(X_tr, ytr)
        ypred = clf.predict(X_te)
        acc = accuracy_score(yte, ypred)
        if acc > best_acc:
            best_acc = acc
            best_ts = ts
    if best_ts is None:
        print(json.dumps({"status": "error", "message": "No valid config"}))
        return

    # Retrain split dengan best_ts
    Xtr, Xte, ytr, yte = train_test_split(docs, y, test_size=best_ts, stratify=y, random_state=42)
    vect = TfidfVectorizer(lowercase=True, token_pattern=r"(?u)\b\w+\b", min_df=1, ngram_range=(1,2))
    X_tr = vect.fit_transform(Xtr)
    X_te = vect.transform(Xte)
    clf = MultinomialNB(alpha=alpha, fit_prior=False)
    clf.fit(X_tr, ytr)
    ypred = clf.predict(X_te)
    acc_final = accuracy_score(yte, ypred)

    out_dir = args.output_dir
    os.makedirs(out_dir, exist_ok=True)

    # Confusion matrix CSV
    labels_str = ['Netral', 'Positif', 'Negatif']
    cm = confusion_matrix(yte, ypred, labels=[0,1,2])
    cm_df = pd.DataFrame(cm, index=labels_str, columns=labels_str)
    cm_csv = os.path.join(out_dir, 'confusion_matrix.csv')
    cm_df.to_csv(cm_csv, index=True)

    # Evaluation split CSV
    report = classification_report(yte, ypred, target_names=labels_str, output_dict=True, zero_division=0)
    df_r = (pd.DataFrame(report).transpose().loc[labels_str, ['precision','recall','f1-score','support']])
    eval_csv = os.path.join(out_dir, 'evaluation_metrics.csv')
    df_r.to_csv(eval_csv, index=True)

    # Plot split evaluation
    plt.figure(figsize=(8,6))
    ax = df_r[['precision','recall','f1-score']].plot(kind='bar', figsize=(8,6), legend=True)
    ax.set_title('Split Evaluation Metrics per Class')
    ax.set_ylabel('Score')
    ax.set_xlabel('Class')
    plt.xticks(rotation=45, ha='right')
    plt.ylim(0, 1.0)
    plt.legend(loc='lower right')
    plt.tight_layout()
    split_img = os.path.join(out_dir, 'evaluation_split.png')
    plt.savefig(split_img, dpi=300)
    plt.close()

    # Retrain full data
    X_full = vect.fit_transform(docs)
    clf_full = MultinomialNB(alpha=alpha, fit_prior=False)
    clf_full.fit(X_full, y)
    model_path = os.path.join(out_dir, 'mnb_final_model.joblib')
    vect_path = os.path.join(out_dir, 'tfidf_vectorizer.joblib')
    joblib.dump(clf_full, model_path)
    joblib.dump(vect, vect_path)

    # Top features per class
    feature_names = vect.get_feature_names_out()
    log_probs = clf_full.feature_log_prob_
    top_feats = {}
    inv_map = {0:'Netral',1:'Positif',2:'Negatif'}
    import numpy as np
    for idx, label in enumerate(clf_full.classes_):
        top_idx = np.argsort(log_probs[idx])[::-1][:15]
        top_feats[inv_map[label]] = [feature_names[i] for i in top_idx]
    rows = []
    for cls, feats in top_feats.items():
        for rank, feat in enumerate(feats, start=1):
            rows.append({'class': cls, 'feature': feat, 'rank': rank})
    df_top = pd.DataFrame(rows)
    top_csv = os.path.join(out_dir, 'top_features_per_class.csv')
    df_top.to_csv(top_csv, index=False)

    # Plot top features
    plt.figure(figsize=(12,18))
    for i, (sent, feat_list) in enumerate(top_feats.items()):
        y_pos = np.arange(len(feat_list))
        plt.subplot(3,1,i+1)
        plt.barh(y_pos, range(len(feat_list),0,-1))
        plt.yticks(y_pos, feat_list)
        plt.title(f'Top 15 Kata - {sent}')
    plt.tight_layout()
    top_img = os.path.join(out_dir, 'top_features.png')
    plt.savefig(top_img)
    plt.close()

    # WordCloud per kelas
    df['label_name'] = df['label'].map(inv_map)
    wc_dir = os.path.join(out_dir, 'wordcloud')
    os.makedirs(wc_dir, exist_ok=True)
    for lbl, text in {lbl: ' '.join(df[df['label_name']==lbl]['clean_text']) for lbl in inv_map.values()}.items():
        if text.strip() == "":
            continue
        wc = WordCloud(width=800, height=400, background_color='white', colormap='viridis', max_words=200).generate(text)
        plt.figure(figsize=(10,5))
        plt.imshow(wc, interpolation='bilinear')
        plt.axis('off')
        path = os.path.join(wc_dir, f'wordcloud_{lbl}.png')
        plt.savefig(path, bbox_inches='tight')
        plt.close()

    # Evaluation full data CSV + plot
    y_full_pred = clf_full.predict(X_full)
    report_full = classification_report(y, y_full_pred, target_names=labels_str, output_dict=True, zero_division=0)
    df_full_eval = pd.DataFrame(report_full).transpose().loc[labels_str, ['precision','recall','f1-score','support']]
    full_csv = os.path.join(out_dir, 'evaluation_full.csv')
    df_full_eval.to_csv(full_csv, index=True)
    plt.figure(figsize=(10,6))
    ax = df_full_eval[['precision','recall','f1-score']].plot(kind='bar')
    for c in ax.containers:
        ax.bar_label(c, fmt='%.2f')
    plt.title('Metrik Evaluasi Full Data')
    plt.ylim(0,1.1)
    plt.tight_layout()
    full_img = os.path.join(out_dir, 'evaluation_full.png')
    plt.savefig(full_img)
    plt.close()

    # Distribution CSV+plot
    counts = df['label'].value_counts().sort_index()
    counts_list = [int(counts.get(i,0)) for i in [0,1,2]]
    dist_df = pd.DataFrame({'label': labels_str, 'count': counts_list})
    dist_csv = os.path.join(out_dir, 'distribution.csv')
    dist_df.to_csv(dist_csv, index=False)
    plt.figure(figsize=(6,4))
    plt.bar(labels_str, counts_list)
    plt.title('Distribusi Sentimen')
    plt.xlabel('Kategori')
    plt.ylabel('Jumlah')
    plt.tight_layout()
    dist_img = os.path.join(out_dir, 'distribution.png')
    plt.savefig(dist_img)
    plt.close()

    # tfidf_all
    vect2 = TfidfVectorizer(lowercase=True, token_pattern=r"(?u)\b\w+\b", min_df=1)
    X2 = vect2.fit_transform(docs)
    vals = np.asarray(X2.sum(axis=0)).ravel()
    terms = vect2.get_feature_names_out()
    tfidf_df = pd.DataFrame({'term': terms, 'tfidf': vals}).sort_values(by='tfidf', ascending=False).reset_index(drop=True)
    tfidf_csv = os.path.join(out_dir, 'tfidf_all.csv')
    tfidf_df.to_csv(tfidf_csv, index=False)

    # Ringkasan
    ringk = os.path.join(out_dir, 'ringkasan.txt')
    with open(ringk, 'w') as f:
        f.write("RINGKASAN HASIL ANALISIS SENTIMEN\n")
        f.write("="*40 + "\n")
        f.write(f"Total data valid: {len(docs)}\n")
        f.write(f"Best test_size: {best_ts}\n")
        f.write(f"Akurasi validasi: {best_acc:.2%}\n")
        f.write(f"Akurasi akhir split: {acc_final:.2%}\n")
        f.write("Top fitur per kelas:\n")
        for lbl, feats in top_feats.items():
            f.write(f"  {lbl}: {', '.join(feats[:10])}\n")

        # === Mulai Simpan full predictions ===
    try:
        inv_map = {0: 'Netral', 1: 'Positif', 2: 'Negatif'}

        # Ambil teks untuk prediksi penuh: prioritas full_text, fallback clean_text
        if 'full_text' in df.columns:
            texts_full = df['full_text'].fillna("").tolist()
            data = {'full_text': df['full_text'].fillna("")}
        else:
            texts_full = df['clean_text'].fillna("").tolist()
            data = {'full_text': df['clean_text'].fillna("")}

        # Sertakan kolom tambahan jika ada
        extra_columns = ['version', 'tweet_date', 'clean_text', 'tokens', 'word_count', 'label']
        for col in extra_columns:
            if col in df.columns:
                data[col] = df[col].values  # gunakan .values untuk memastikan panjang data konsisten

        if texts_full:
            # Transform dan prediksi
            X_all = vect.transform(texts_full)
            preds = clf_full.predict(X_all)

            # Probabilitas
            try:
                probas = clf_full.predict_proba(X_all)
                class_indices = {int(c): i for i, c in enumerate(clf_full.classes_)}
                proba_df = pd.DataFrame({
                    'proba_Netral': probas[:, class_indices[0]],
                    'proba_Positif': probas[:, class_indices[1]],
                    'proba_Negatif': probas[:, class_indices[2]],
                })
            except Exception as e:
                print(f"Gagal menghitung probabilitas: {e}", file=sys.stderr)
                proba_df = None

            # Predicted label
            data['predicted_label'] = [inv_map.get(int(p), str(p)) for p in preds]

            # Jika ada kolom label asli, map ke nama
            if 'label' in df.columns:
                data['true_label'] = df['label'].map(inv_map)

            # Tambahkan probabilitas
            if proba_df is not None:
                data['proba_Netral'] = proba_df['proba_Netral'].values
                data['proba_Positif'] = proba_df['proba_Positif'].values
                data['proba_Negatif'] = proba_df['proba_Negatif'].values

            # Susun DataFrame dan simpan
            df_full = pd.DataFrame(data)
            csv_full = os.path.join(out_dir, 'df_full_predictions.csv')
            df_full.to_csv(csv_full, index=False)
            print(f"Saved full predictions to {csv_full}", file=sys.stderr)

            if 'version' not in df_full.columns:
                print("WARNING: Kolom 'version' tidak ditemukan di df_full_predictions", file=sys.stderr)

        else:
            print("Skip saving full predictions karena teks tidak tersedia.", file=sys.stderr)

    except Exception as e:
        print(f"Error saving full predictions: {e}", file=sys.stderr)
    # === Selesai Simpan full predictions ===


    # Output JSON paths untuk Laravel
    result = {
        "status":"success",
        "model": model_path,
        "vectorizer": vect_path,
        "confusion_csv": cm_csv,
        "evaluation_csv": eval_csv,
        "top_features_csv": top_csv,
        "top_features_img": top_img,
        "evaluation_full_csv": full_csv,
        "evaluation_full_img": full_img,
        "distribution_csv": dist_csv,
        "distribution_img": dist_img,
        "tfidf_csv": tfidf_csv,
        "wordcloud_dir": wc_dir,
        "ringkasan": ringk
    }
    print(json.dumps(result, ensure_ascii=False))


def sub_infer(args):
    model_path=args.model; vect_path=args.vectorizer; texts_json=args.texts
    try: texts=json.loads(texts_json)
    except:
        print(json.dumps({"status":"error","message":"Invalid texts JSON"})); return
    clf=joblib.load(model_path); vect=joblib.load(vect_path)
    X=vect.transform(texts); preds=clf.predict(X); probas=clf.predict_proba(X)
    inv_map={0:'Netral',1:'Positif',2:'Negatif'}
    results=[]
    for txt,p,prob in zip(texts,preds,probas):
        results.append({
            "text":txt,
            "prediction":inv_map.get(int(p),str(p)),
            "probabilities":{"Netral":float(prob[0]),"Positif":float(prob[1]),"Negatif":float(prob[2])}
        })
    print(json.dumps({"status":"success","results":results}, ensure_ascii=False))



def main():
    parser=argparse.ArgumentParser(description="Sentiment analysis service")
    sub=parser.add_subparsers(dest='cmd')
    p=sub.add_parser('train'); p.add_argument('--data', required=True); p.add_argument('--output-dir', required=True)
    p2=sub.add_parser('infer'); p2.add_argument('--model', required=True); p2.add_argument('--vectorizer', required=True); p2.add_argument('--texts', required=True)
    args=parser.parse_args()
    if args.cmd=='train': sub_train(args)
    elif args.cmd=='infer': sub_infer(args)
    else:
        parser.print_help(); sys.exit(1)

if __name__=='__main__':
    main()
