import re,sys
SR='@OA\\Schema(ref="#/components/schemas/ServerResponse")'
def match_paren(s,i):
  # s[i]=='(' ; return index of matching ')'
  d=0;q=None
  for j in range(i,len(s)):
    c=s[j]
    if q:
      if c==q and s[j-1]!='\\': q=None
      continue
    if c in '"\'': q=c;continue
    if c=='(': d+=1
    elif c==')':
      d-=1
      if d==0: return j
  raise ValueError
def strip_stars(t): return re.sub(r'\n\s*\* ?','\n',t)
def unwrap(src):
  out=src;pos=0;left=0
  while True:
    i=out.find('@OA\\JsonContent(',pos)
    if i<0: break
    o=out.index('(',i);c=match_paren(out,o)
    body=out[o+1:c]
    flat=re.sub(r'\s*\n\s*\*\s*',' ',body)
    m=re.match(r'\s*allOf=\{\s*'+re.escape(SR)+r',\s*@OA\\Schema\(',flat)
    if m:
      # locate inner schema in original body by scanning
      bi=body.index('@OA\\Schema(',body.index(SR)+len(SR))
      bo=body.index('(',bi);bc=match_paren(body,bo)
      inner=body[bo+1:bc]
      rest=body[bc+1:]
      if re.sub(r'[\s*,}]','',rest)=='' :
        out=out[:o+1]+inner+out[c:]
        pos=o+1;continue
      else: left+=1
    pos=c+1
  return out,left
def fix(path,codes=None):
  s=open(path).read()
  s,left=unwrap(s)
  # remaining plain refs
  def rr(m):
    code=m.group(1)
    if code.startswith('2'): return '@OA\\Response(response="%s", description="%s")'%(code,m.group(2))
    ref='ValidationErrorResponse' if code=='422' else 'ErrorResponse'
    return '@OA\\Response(response="%s", description="%s", @OA\\JsonContent(ref="#/components/schemas/%s"))'%(code,m.group(2),ref)
  s=re.sub(r'@OA\\Response\(response="(\d+)", description="([^"]*)", @OA\\JsonContent\(ref="#/components/schemas/ServerResponse"\)\)',rr,s)
  open(path,'w').write(s)
  print(path,'unhandled allOf:',left,'remaining ServerResponse refs:',s.count('ServerResponse'))
if __name__=='__main__':
  for p in sys.argv[1:]: fix(p)
