Name:          storytlr
Version:       1.0.1
Release:       1%{?dist}
Summary:       Storytlr is an opensource lifestreaming and microblogging platform.
Packager:      Laurent Eschenauer <laurent@eschenauer.be>
Group:         Applications/Internet
License:       Apache 2.0 License
URL:           https://github.com/storytlr/storytlr
Source0:       %{url}/tarball/release-%{version}
BuildRoot:     %{_tmppath}/%{name}-%{version}-%{release}-tmp
Provides:      storytlr
Requires:      php
Requires:      httpd

%description
Storytlr is an opensource lifestreaming and microblogging platform.

%prep
%setup -c -q -n %{name}-%{version}

%build

%install
%{__mkdir} -p %{buildroot}/usr/share/storytlr
%{__mkdir} -p $RPM_BUILD_ROOT%{_sysconfdir}/storytlr
%{__mkdir} -p $RPM_BUILD_ROOT%{_localstatedir}/lib/storytlr/feeds
%{__mkdir} -p $RPM_BUILD_ROOT%{_localstatedir}/lib/storytlr/temp
%{__mkdir} -p $RPM_BUILD_ROOT%{_localstatedir}/lib/storytlr/uploads
%{__mkdir} -p $RPM_BUILD_ROOT%{_localstatedir}/log/storytlr

%{__cp} -r storytlr*/* %{buildroot}/usr/share/storytlr/
%{__cp} storytlr*/protected/build/rpmbuild/etc/storytlr/storytlr.conf $RPM_BUILD_ROOT%{_sysconfdir}/storytlr/storytlr.conf
%{__cp} storytlr*/protected/build/rpmbuild/etc/httpd/conf.d/storytlr.conf $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/storytlr.conf

%post

%postun

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(0644,root,root,0755)
%config(noreplace) %{_sysconfdir}/storytlr/storytlr.conf
%dir /usr/share/storytlr
/usr/share/storytlr/*
%defattr(0644,apache,apache,0755)
%dir %{_localstatedir}/lib/storytlr/feeds
%dir %{_localstatedir}/lib/storytlr/temp
%dir %{_localstatedir}/lib/storytlr/uploads
%dir %{_localstatedir}/log/storytlr

%changelog
* Sat Jan 18 2013 Laurent Eschenauer <laurent@eschenauer.be> - 1.2.0
- Initial release.

