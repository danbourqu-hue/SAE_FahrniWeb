"""Erzeugt Hochformat-Videos (9:16, Reel-Format) für die Beispielbeiträge der Social-Media-Seite.

Aus jedem Bild wird ein langsamer Kameraschwenk, mehrere Bilder werden weich überblendet.
Aufruf: python3 content/make_social_videos.py   (braucht ffmpeg)
Ausgabe: assets/video/social/<id>.mp4 und <id>.jpg (Vorschaubild)
"""
import json, pathlib, subprocess

HERE = pathlib.Path(__file__).resolve().parent.parent
SOCIAL = json.loads((HERE / "content/social.json").read_text(encoding="utf-8"))
OUT = HERE / "assets/video/social"; OUT.mkdir(parents=True, exist_ok=True)
W, H, FPS, SEG_DEFAULT, FADE = 720, 1280, 25, 3.4, .7

def clip_filter(i, n, direction, SEG):
    # Bild auf Höhe bringen, dann horizontal schwenken (abwechselnd links/rechts) und leicht zoomen
    x = f"(iw-{W})*t/{SEG}" if direction else f"(iw-{W})*(1-t/{SEG})"
    return (f"[{i}:v]scale=-2:{int(H*1.12)},crop={W}:{H}:'{x}':'(ih-{H})/2',"
            f"fps={FPS},format=yuv420p,setsar=1,trim=duration={SEG},setpts=PTS-STARTPTS[v{i}]")

for post in SOCIAL["posts"]:
    dest = OUT / f"{post['id']}.mp4"
    if post.get("video"):
        continue  # vorhandenes Video (z. B. Footer-Animation) wird direkt verwendet
    imgs = [HERE / "assets" / p for p in post["images"]]
    SEG = post.get("seg", SEG_DEFAULT)
    args = ["ffmpeg", "-v", "error", "-y"]
    for p in imgs: args += ["-loop", "1", "-t", str(SEG), "-i", str(p)]
    parts = [clip_filter(i, len(imgs), i % 2 == 0, SEG) for i in range(len(imgs))]
    last, offset = "v0", SEG - FADE
    for i in range(1, len(imgs)):
        parts.append(f"[{last}][v{i}]xfade=transition=fade:duration={FADE}:offset={offset:.2f}[x{i}]")
        last, offset = f"x{i}", offset + SEG - FADE
    total = SEG * len(imgs) - FADE * (len(imgs) - 1)
    parts.append(f"[{last}]fade=t=in:st=0:d=0.5,fade=t=out:st={total-0.6:.2f}:d=0.6[out]")
    args += ["-filter_complex", ";".join(parts), "-map", "[out]", "-c:v", "libx264", "-preset", "slow", "-crf", "27",
             "-pix_fmt", "yuv420p", "-movflags", "+faststart", "-an", str(dest)]
    subprocess.run(args, check=True)
    subprocess.run(["ffmpeg", "-v", "error", "-y", "-ss", "1.2", "-i", str(dest), "-frames:v", "1", "-q:v", "4", str(OUT / f"{post['id']}.jpg")], check=True)
    print(post["id"], f"{dest.stat().st_size/1024:.0f} KB", f"{total:.1f}s")
