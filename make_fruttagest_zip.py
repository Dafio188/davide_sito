import os
import zipfile
import glob

def zip_fruttagest_update(output_filename):
    # Core changed files
    includes = [
        'index.html',
        'js/main.js',
        'css/fruttagest.css',
    ]
    
    # Add all fruttagest thumbnail images
    image_files = glob.glob('fruttagest_homepage*.png')
    includes.extend(image_files)
    
    with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for item in includes:
            if os.path.exists(item):
                zipf.write(item)
            else:
                print(f"Warning: {item} not found")

if __name__ == "__main__":
    zip_fruttagest_update('update_fruttagest_aruba.zip')
    print("Zip created: update_fruttagest_aruba.zip")
