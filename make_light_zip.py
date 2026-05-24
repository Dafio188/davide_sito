import os
import zipfile

def zip_light_update(output_filename):
    # Files to include (Only changed files + standard structure)
    includes = [
        'index.html',
        'js/main.js',
        'js/chatbot.js',
        'css/style.css',
        'api/chat.php',
        'api/config.php',
        'api/index.php',
        'api/.htaccess',
        'CHATBOT_SETUP.md'
    ]
    
    with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for item in includes:
            if os.path.exists(item):
                zipf.write(item)
            else:
                print(f"Warning: {item} not found")

if __name__ == "__main__":
    zip_light_update('deploy_light_update.zip')
    print("Zip created: deploy_light_update.zip")
