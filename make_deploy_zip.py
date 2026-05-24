import os
import zipfile

def zip_website(output_filename):
    # Files/Dirs to include
    includes = [
        'index.html',
        'ai-rag.html', 
        'ai-training.html', 
        'crm-gestionali.html', 
        'performance-seo.html', 
        'scroll-video.html', 
        'data-globe-3d.html',
        'web-development.html', 
        'privacy_policy.html', 
        'cookie_policy.html',
        '.htaccess',
        '.well-known',
        'robots.txt',
        'sitemap.xml',
        'api',
        'assets',
        'css',
        'js',
        'videos'
    ]
    
    # Extensions/Files to exclude
    excludes = [
        '.DS_Store',
        'Thumbs.db',
        '.git',
        '.gitignore',
        'deploy_rescue_fix_v2.zip',
        'ezgif-1a14355172754500-jpg.zip',
        'deploy_security_update.zip',
        'deploy_v8_premium_final.zip',
        'make_deploy_zip.py'
    ]

    with zipfile.ZipFile(output_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for item in includes:
            if os.path.isfile(item):
                zipf.write(item, arcname=item)
            elif os.path.isdir(item):
                for root, dirs, files in os.walk(item):
                    # Filter out excluded directories
                    dirs[:] = [d for d in dirs if d not in excludes]
                    
                    for file in files:
                        if file not in excludes and not file.endswith('.zip') and not file.endswith('.log'):
                            # Create a relative path for the archive to avoid full paths
                            file_path = os.path.join(root, file)
                            # Use relpath to ensure clean structure inside zip
                            arcname = os.path.relpath(file_path, start='.')
                            zipf.write(file_path, arcname=arcname)

if __name__ == "__main__":
    zip_website('deploy_v25.zip')
    print("Zip created: deploy_v25.zip")
