import sys
import subprocess

def install_pillow():
    subprocess.check_call([sys.executable, "-m", "pip", "install", "Pillow"])

try:
    from PIL import Image, ImageDraw, ImageOps
except ImportError:
    print("Pillow not found, installing...")
    install_pillow()
    from PIL import Image, ImageDraw, ImageOps

def create_circular_favicon(input_path, output_path, size=(64, 64)):
    try:
        img = Image.open(input_path).convert("RGBA")
        
        # Resize for favicon (quality)
        img = img.resize(size, Image.Resampling.LANCZOS)
        
        # Create mask
        mask = Image.new('L', size, 0)
        draw = ImageDraw.Draw(mask) 
        draw.ellipse((0, 0) + size, fill=255)
        
        # Apply mask
        output = ImageOps.fit(img, mask.size, centering=(0.5, 0.5))
        output.putalpha(mask)
        
        output.save(output_path, "PNG")
        print(f"Successfully created {output_path}")
        
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    create_circular_favicon("assets/avatar_new.png", "assets/favicon.png")
