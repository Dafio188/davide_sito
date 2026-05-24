import zipfile
import os
from datetime import datetime

def create_backup():
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    zip_name = f"backup_pre_tubes_{timestamp}.zip"
    
    files_to_backup = [
        'index.html',
        'css/style.css',
        'js/main.js' 
    ]
    
    with zipfile.ZipFile(zip_name, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for file in files_to_backup:
            if os.path.exists(file):
                zipf.write(file)
                print(f"Backed up: {file}")
            else:
                print(f"Warning: {file} not found")
    
    print(f"Backup created: {zip_name}")

if __name__ == "__main__":
    create_backup()
