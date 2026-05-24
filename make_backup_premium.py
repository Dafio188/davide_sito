import zipfile
import os
from datetime import datetime

def create_backup():
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    zip_name = f"backup_pre_premium_fx_{timestamp}.zip"
    
    # Backup all main files
    files_to_backup = [
        'index.html',
        'css/style.css',
        'js/main.js',
        'js/glowing-tubes.js'
    ]
    
    # Add internal pages
    for f in os.listdir('.'):
        if f.endswith('.html') and f != 'index.html':
            files_to_backup.append(f)
    
    with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for file in files_to_backup:
            if os.path.exists(file):
                zipf.write(file)
    
    print(f"Backup created: {zip_name}")

if __name__ == "__main__":
    create_backup()
